#!/usr/bin/env python3
"""Process fixed-format deployment requests outside the PHP web process."""

import argparse
import fcntl
import hashlib
import json
import os
import pathlib
import pwd
import re
import stat
import subprocess
import tarfile
import threading
import time
import urllib.error
import urllib.parse
import urllib.request
import uuid

TAG = re.compile(r"^msp-\d{4}\.\d{1,2}\.\d{1,2}\.\d{1,4}$")
MIGRATION = re.compile(r"^(database/migrations/|packages/(?:[^/]+/)+src/Database/Migrations/).+\.php$")
ALLOWED_HOSTS = {"api.github.com", "github.com", "release-assets.githubusercontent.com", "objects.githubusercontent.com", "github-releases.githubusercontent.com"}


class UpdateError(Exception):
    """A bounded public error classification with no private process output."""


class NoRedirect(urllib.request.HTTPRedirectHandler):
    """Require explicit validation before following a release asset redirect."""

    def redirect_request(self, request, file_pointer, code, message, headers, new_url):
        return None


def validate_manifest(document, release):
    """Bind the project, version, source and archive to one published release."""
    tag = document.get("release_id", "")
    archive = document.get("archive", {})
    if (document.get("schema") != 1 or document.get("updater_protocol") != 1 or document.get("project") != "mansecretpleasure"
            or not isinstance(tag, str) or not TAG.fullmatch(tag)
            or tag != release.get("tag_name") or document.get("version") != tag[4:]
            or release.get("draft") is not False or release.get("prerelease") is not False
            or document.get("database_policy") != "unchanged"
            or not isinstance(document.get("commit"), str)
            or not re.fullmatch(r"[a-f0-9]{40}", document["commit"])
            or not isinstance(archive, dict) or archive.get("name") != tag + ".tar.gz"
            or not re.fullmatch(r"[a-f0-9]{64}", str(archive.get("sha256", "")))):
        raise UpdateError("failed")
    assets = [asset for asset in release.get("assets", []) if asset.get("name") == archive["name"]]
    if len(assets) != 1:
        raise UpdateError("failed")
    asset = assets[0]
    if (type(asset.get("id")) is not int or asset["id"] < 1
            or type(asset.get("size")) is not int or not 0 < asset["size"] <= 1073741824
            or (asset.get("digest") is not None and asset["digest"] != "sha256:" + archive["sha256"])):
        raise UpdateError("failed")
    return asset


def migration_inventory(directory):
    """Inventory only application and package migration files without following links."""
    directory = pathlib.Path(directory)
    result = {}
    candidates = list(directory.glob("database/migrations/**/*.php"))
    candidates += list(directory.glob("packages/**/src/Database/Migrations/**/*.php"))
    for file in candidates:
        if (file.is_symlink() or any(parent.is_symlink() for parent in file.parents if parent.is_relative_to(directory))
                or not file.is_file() or not file.resolve().is_relative_to(directory.resolve())):
            raise UpdateError("database-review")
        result[file.relative_to(directory).as_posix()] = hashlib.sha256(file.read_bytes()).hexdigest()
    return result


def inspect_archive(archive_path, manifest):
    """Reject unsafe members and read migration hashes before staging any source."""
    result, names, size, embedded = {}, set(), 0, None
    with tarfile.open(archive_path, "r:gz") as archive:
        for member in archive:
            path = pathlib.PurePosixPath(member.name)
            if (path.is_absolute() or ".." in path.parts or path.as_posix() in names
                    or not (member.isdir() or member.isfile())
                    or any(part.startswith(".env") or part in {".git", ".local", "vendor", "node_modules", "storage"} for part in path.parts)
                    or member.name.endswith((".hot", ".pem", ".key"))):
                raise UpdateError("failed")
            names.add(path.as_posix())
            size += member.size
            if len(names) > 100000 or size > 1073741824:
                raise UpdateError("failed")
            name = path.as_posix()
            if member.isfile() and (MIGRATION.fullmatch(name) or name == "release.json"):
                if member.size > 2097152:
                    raise UpdateError("failed")
                body = archive.extractfile(member).read()
                if name == "release.json":
                    embedded = json.loads(body)
                else:
                    result[name] = hashlib.sha256(body).hexdigest()
    if not isinstance(embedded, dict) or any(embedded.get(key) != manifest.get(key) for key in ["schema", "updater_protocol", "project", "release_id", "version", "commit"]):
        raise UpdateError("failed")
    return result


def atomic_json(path, document, group=None):
    """Replace metadata atomically with private or worker-readable permissions."""
    path = pathlib.Path(path)
    temporary = path.with_name(path.name + "." + uuid.uuid4().hex + ".pending")
    descriptor = os.open(temporary, os.O_CREAT | os.O_EXCL | os.O_WRONLY | os.O_NOFOLLOW, 0o600)
    try:
        with os.fdopen(descriptor, "w") as stream:
            json.dump(document, stream)
            stream.flush()
            os.fsync(stream.fileno())
        if group is not None:
            os.chown(temporary, 0, group)
            os.chmod(temporary, 0o640)
        os.replace(temporary, path)
    finally:
        temporary.unlink(missing_ok=True)


class Worker:
    """Use root-owned tooling and state; requests can select only approved releases."""

    def __init__(self, config, state_root="/var/lib/manpleasure-updater", inbox="/var/spool/manpleasure-updater/requests"):
        self.config = config
        self.repository = config["repository"]
        if not re.fullmatch(r"[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+", self.repository):
            raise UpdateError("failed")
        self.base = pathlib.Path("/var/www/mansecretpleasure")
        self.root = pathlib.Path(state_root)
        self.inbox = pathlib.Path(inbox)
        self.public = self.root / "public"
        self.tools = pathlib.Path("/usr/local/lib/manpleasure-updater")
        self.web_user = pwd.getpwnam("www-data")
        self.deploy_user = pwd.getpwnam("mansecret-deploy")
        self.active = None
        self.log = None

    def read_state(self):
        """Read only root-owned deployment history."""
        file = self.root / "state.json"
        return json.loads(file.read_text()) if file.exists() else {}

    def current(self):
        """Require the established current-release symlink and a real release directory."""
        current = self.base / "current"
        if not current.is_symlink():
            raise UpdateError("failed")
        target = current.resolve(strict=True)
        if target.parent != (self.base / "releases").resolve() or not (target / "artisan").is_file():
            raise UpdateError("failed")
        return target

    def publish(self, operation, phase, code=None):
        """Expose a bounded operation status without commands, logs or credentials."""
        atomic_json(self.public / (operation + ".json"), {"phase": phase, "code": code, "timestamp": int(time.time())}, self.web_user.pw_gid)

    def pulse(self, stop):
        """Keep availability fresh while a long build or backup is running."""
        while not stop.is_set():
            state = self.read_state()
            atomic_json(self.public / "heartbeat.json", {
                "protocol": 1, "repository": self.repository, "timestamp": int(time.time()),
                "active": self.active, "rollback": bool(state.get("previous")),
            }, self.web_user.pw_gid)
            stop.wait(10)

    def fetch(self, api_path, asset=False, destination=None, maximum=65536):
        """Download through a fixed API and validated CDN redirects with bounded size."""
        url = "https://api.github.com/repos/" + self.repository + api_path
        opener = urllib.request.build_opener(NoRedirect())
        for _ in range(4):
            parsed = urllib.parse.urlsplit(url)
            if (parsed.scheme != "https" or parsed.hostname not in ALLOWED_HOSTS or parsed.username or parsed.password
                    or parsed.port not in (None, 443)
                    or (parsed.hostname == "github.com" and not parsed.path.startswith("/" + self.repository + "/releases/download/"))):
                raise UpdateError("failed")
            headers = {"Accept": "application/octet-stream" if asset else "application/vnd.github+json", "User-Agent": "ManPleasure-Updater/1"}
            if parsed.hostname == "api.github.com" and self.config.get("token"):
                headers["Authorization"] = "Bearer " + self.config["token"]
            try:
                response = opener.open(urllib.request.Request(url, headers=headers), timeout=30)
            except urllib.error.HTTPError as error:
                response = error
            with response:
                if asset and response.code in (301, 302, 303, 307, 308):
                    url = response.headers.get("Location", "")
                    continue
                if response.code != 200:
                    raise UpdateError("failed")
                total, chunks = 0, []
                output = open(destination, "xb") if destination is not None else None
                try:
                    while True:
                        chunk = response.read(1048576)
                        if not chunk:
                            break
                        total += len(chunk)
                        if total > maximum:
                            raise UpdateError("failed")
                        if output:
                            output.write(chunk)
                        else:
                            chunks.append(chunk)
                finally:
                    if output:
                        output.close()
                return b"".join(chunks) if destination is None else None
        raise UpdateError("failed")

    def run(self, command, timeout=1200):
        """Execute a fixed argument vector and retain output in a root-private log."""
        subprocess.run(command, check=True, timeout=timeout, stdin=subprocess.DEVNULL, stdout=self.log, stderr=subprocess.STDOUT)

    def health(self):
        """Verify the local HTTPS origin after switching the release."""
        for _ in range(15):
            result = subprocess.run([
                "/usr/bin/curl", "--fail", "--silent", "--max-time", "10", "--resolve",
                "mansecretpleasure.com:443:127.0.0.1", "https://mansecretpleasure.com/up",
            ], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL, timeout=15)
            if result.returncode == 0:
                return
            time.sleep(2)
        raise UpdateError("failed")

    def activate(self, target, previous):
        """Use the reviewed backup/activation tooling and recover on failed health checks."""
        try:
            self.run(["/usr/bin/bash", str(self.tools / "activate-release.sh"), target.name, "--data-reviewed"])
            self.publish(self.active, "verifying")
            self.health()
        except Exception:
            self.run(["/usr/bin/bash", str(self.tools / "rollback-release.sh"), previous.name, "--schema-compatible"])
            self.health()
            raise
        atomic_json(self.root / "state.json", {"current": target.name, "previous": previous.name})

    def install(self, request, previous):
        """Re-fetch release metadata, verify the archive and preserve the migration tree."""
        release_id = request.get("release_api_id")
        if type(release_id) is not int or release_id < 1 or not re.fullmatch(r"[a-f0-9]{64}", str(request.get("sha256", ""))):
            raise UpdateError("failed")
        release = json.loads(self.fetch("/releases/" + str(release_id), maximum=4194304))
        if release.get("id") != release_id:
            raise UpdateError("failed")
        manifests = [asset for asset in release.get("assets", []) if asset.get("name") == "manpleasure-release.json"]
        if len(manifests) != 1 or type(manifests[0].get("id")) is not int or manifests[0]["id"] < 1 or not 0 < manifests[0].get("size", 0) <= 65536:
            raise UpdateError("failed")
        manifest = json.loads(self.fetch("/releases/assets/" + str(manifests[0]["id"]), asset=True))
        asset = validate_manifest(manifest, release)
        if manifest["archive"]["sha256"] != request["sha256"]:
            raise UpdateError("failed")
        installed_metadata = previous / "release.json"
        if installed_metadata.is_file():
            installed = json.loads(installed_metadata.read_text())
            if installed.get("project") == "mansecretpleasure" and installed.get("version"):
                if tuple(map(int, manifest["version"].split("."))) <= tuple(map(int, installed["version"].split("."))):
                    raise UpdateError("failed")
        target = self.base / "releases" / manifest["release_id"]
        if target.exists() or target.is_symlink():
            raise UpdateError("failed")
        archive = self.root / "artifacts" / (self.active + ".tar.gz")
        self.publish(self.active, "downloading")
        self.fetch("/releases/assets/" + str(asset["id"]), asset=True, destination=archive, maximum=1073741824)
        with open(archive, "rb") as stream:
            if hashlib.file_digest(stream, "sha256").hexdigest() != request["sha256"]:
                raise UpdateError("failed")
        if inspect_archive(archive, manifest) != migration_inventory(previous):
            raise UpdateError("database-review")
        os.chown(archive, 0, self.deploy_user.pw_gid)
        os.chmod(archive, 0o640)
        self.publish(self.active, "staging")
        self.run(["/usr/sbin/runuser", "-u", "mansecret-deploy", "--", "/usr/bin/bash", str(self.tools / "stage-release.sh"), str(archive), target.name, request["sha256"]])
        if migration_inventory(target) != migration_inventory(previous):
            raise UpdateError("database-review")
        self.publish(self.active, "activating")
        self.activate(target, previous)
        archive.unlink(missing_ok=True)

    def rollback(self, previous):
        """Restore only the worker's recorded previous code, preserving current data."""
        name = self.read_state().get("previous")
        if not isinstance(name, str) or not re.fullmatch(r"[A-Za-z0-9][A-Za-z0-9._-]{0,127}", name):
            raise UpdateError("failed")
        target = self.base / "releases" / name
        if target.is_symlink() or not target.is_dir() or target.resolve().parent != (self.base / "releases").resolve():
            raise UpdateError("failed")
        if migration_inventory(target) != migration_inventory(previous):
            raise UpdateError("database-review")
        self.publish(self.active, "activating")
        self.activate(target, previous)

    def process(self, file):
        """Consume one fresh web-owned regular request, rejecting links and replays."""
        operation = file.stem
        try:
            if str(uuid.UUID(operation)) != operation:
                return
            if (self.public / (operation + ".json")).exists():
                return
            descriptor = os.open(file, os.O_RDONLY | os.O_NOFOLLOW | os.O_NONBLOCK)
            with os.fdopen(descriptor, "rb") as stream:
                info = os.fstat(stream.fileno())
                if not stat.S_ISREG(info.st_mode) or info.st_uid != self.web_user.pw_uid or info.st_nlink != 1 or info.st_size > 4096:
                    raise UpdateError("failed")
                request = json.loads(stream.read(4097))
            if (request.get("id") != operation or type(request.get("created_at")) is not int
                    or abs(time.time() - request["created_at"]) > 600
                    or type(request.get("actor")) is not int or request["actor"] not in self.config["owner_ids"]):
                raise UpdateError("failed")
            previous = self.current()
            if request.get("expected_current") != previous.name:
                raise UpdateError("failed")
            self.active = operation
            self.publish(operation, "queued")
            with open(self.root / "logs" / (operation + ".log"), "xb") as self.log:
                if request.get("action") == "install":
                    self.install(request, previous)
                elif request.get("action") == "rollback":
                    self.rollback(previous)
                else:
                    raise UpdateError("failed")
            self.publish(operation, "rolled-back" if request["action"] == "rollback" else "completed")
        except Exception as error:
            if re.fullmatch(r"[a-f0-9-]{36}", operation):
                self.publish(operation, "failed", "database-review" if isinstance(error, UpdateError) and str(error) == "database-review" else "failed")
        finally:
            self.active = None
            self.log = None
            (self.root / "artifacts" / (operation + ".tar.gz")).unlink(missing_ok=True)
            file.unlink(missing_ok=True)


def main():
    """Run the fixed-location daemon as root with a single global worker lock."""
    parser = argparse.ArgumentParser()
    parser.add_argument("--once", action="store_true")
    args = parser.parse_args()
    if os.geteuid() != 0:
        raise SystemExit("This worker must be installed as the dedicated server service.")
    config_file = pathlib.Path("/etc/manpleasure-updater.json")
    info = config_file.stat()
    if info.st_uid != 0 or info.st_mode & 0o077 or config_file.is_symlink():
        raise SystemExit("Updater configuration permissions are invalid.")
    config = json.loads(config_file.read_text())
    if not config.get("owner_ids") or any(type(value) is not int or value < 1 for value in config["owner_ids"]):
        raise SystemExit("Configure the permitted owner IDs first.")
    os.umask(0o077)
    worker = Worker(config)
    worker.current()
    with open(worker.root / "worker.lock", "a") as lock:
        fcntl.flock(lock, fcntl.LOCK_EX | fcntl.LOCK_NB)
        stop = threading.Event()
        threading.Thread(target=worker.pulse, args=(stop,), daemon=True).start()
        try:
            while True:
                for file in sorted(worker.inbox.glob("*.json"))[:100]:
                    worker.process(file)
                if args.once:
                    break
                stop.wait(2)
        finally:
            stop.set()


if __name__ == "__main__":
    main()
