"""Exercise deployment boundaries without a server, database, or network."""
import copy
import importlib.util
import io
import json
import pathlib
import tarfile
import tempfile
import unittest
import time
import types
import uuid
from unittest.mock import Mock, patch

SOURCE = pathlib.Path(__file__).resolve().parents[2] / 'scripts/deploy/admin-updater/worker.py'
spec = importlib.util.spec_from_file_location('manpleasure_worker', SOURCE)
worker = importlib.util.module_from_spec(spec)
spec.loader.exec_module(worker)


def fixture():
    manifest = {'schema': 1, 'updater_protocol': 1, 'project': 'mansecretpleasure',
                'release_id': 'msp-2026.10.17.1', 'version': '2026.10.17.1', 'commit': 'b' * 40,
                'database_policy': 'unchanged', 'archive': {'name': 'msp-2026.10.17.1.tar.gz', 'sha256': 'a' * 64}}
    release = {'id': 10, 'tag_name': manifest['release_id'], 'draft': False, 'prerelease': False,
               'assets': [{'id': 20, 'name': manifest['archive']['name'], 'size': 100, 'digest': 'sha256:' + 'a' * 64}]}
    return manifest, release


def archive_file(path, entries, manifest=None):
    with tarfile.open(path, 'w:gz') as output:
        members = [('release.json', json.dumps(manifest or fixture()[0]).encode())] + entries
        for name, body in members:
            info = tarfile.TarInfo(name)
            if body is None:
                info.type = tarfile.SYMTYPE
                info.linkname = '/etc/passwd'
                output.addfile(info)
            else:
                info.size = len(body)
                output.addfile(info, io.BytesIO(body))


class ManifestTests(unittest.TestCase):
    def test_stable_release(self):
        manifest, release = fixture()
        self.assertEqual(worker.validate_manifest(manifest, release)['id'], 20)

    def test_manifest_boundaries(self):
        for field, value in [('schema', 2), ('updater_protocol', 2), ('project', 'bagisto'),
                             ('release_id', '../escape'), ('version', '9'), ('commit', 'no'), ('database_policy', 'migrate')]:
            with self.subTest(field=field):
                manifest, release = fixture()
                manifest[field] = value
                with self.assertRaises(worker.UpdateError):
                    worker.validate_manifest(manifest, release)

    def test_release_boundaries(self):
        for mutation in ['draft', 'prerelease', 'duplicate', 'missing', 'oversized', 'digest']:
            with self.subTest(mutation=mutation):
                manifest, release = fixture()
                if mutation in ['draft', 'prerelease']:
                    release[mutation] = True
                elif mutation == 'duplicate':
                    release['assets'].append(copy.deepcopy(release['assets'][0]))
                elif mutation == 'missing':
                    release['assets'] = []
                elif mutation == 'oversized':
                    release['assets'][0]['size'] = 1073741825
                else:
                    release['assets'][0]['digest'] = 'sha256:' + 'c' * 64
                with self.assertRaises(worker.UpdateError):
                    worker.validate_manifest(manifest, release)


class ArchiveTests(unittest.TestCase):
    def test_webkul_and_app_migrations_are_inventoried(self):
        with tempfile.TemporaryDirectory() as directory:
            root = pathlib.Path(directory)
            entries = [('database/migrations/a.php', b'app'), ('packages/Webkul/User/src/Database/Migrations/b.php', b'package')]
            for name, body in entries:
                target = root / name
                target.parent.mkdir(parents=True, exist_ok=True)
                target.write_bytes(body)
            archive_file(root / 'release.tar.gz', entries)
            self.assertEqual(worker.inspect_archive(root / 'release.tar.gz', fixture()[0]), worker.migration_inventory(root))
            self.assertEqual(len(worker.migration_inventory(root)), 2)

    def test_unsafe_archive_members(self):
        for entry in [('../escape', b'x'), ('/absolute', b'x'), ('app/link', None), ('.env', b'secret'),
                      ('app/.env.production', b'x'), ('vendor/file', b'x'), ('public/storage/file', b'x'), ('key.pem', b'x')]:
            with self.subTest(name=entry[0]), tempfile.TemporaryDirectory() as directory:
                archive = pathlib.Path(directory) / 'release.tar.gz'
                archive_file(archive, [entry])
                with self.assertRaises(worker.UpdateError):
                    worker.inspect_archive(archive, fixture()[0])

    def test_canonical_duplicate_and_metadata_mismatch(self):
        with tempfile.TemporaryDirectory() as directory:
            archive = pathlib.Path(directory) / 'release.tar.gz'
            archive_file(archive, [('app/a', b'first'), ('./app/a', b'second')])
            with self.assertRaises(worker.UpdateError):
                worker.inspect_archive(archive, fixture()[0])
            manifest = fixture()[0]
            manifest['commit'] = 'c' * 40
            archive_file(archive, [], manifest)
            with self.assertRaises(worker.UpdateError):
                worker.inspect_archive(archive, fixture()[0])

    def test_migration_links_rejected(self):
        with tempfile.TemporaryDirectory() as directory:
            root = pathlib.Path(directory)
            target = root / 'database/migrations/linked.php'
            target.parent.mkdir(parents=True)
            target.symlink_to('/etc/passwd')
            with self.assertRaises(worker.UpdateError):
                worker.migration_inventory(root)


class RecoveryTests(unittest.TestCase):
    def make_worker(self, root):
        instance = worker.Worker.__new__(worker.Worker)
        instance.root = pathlib.Path(root)
        instance.tools = pathlib.Path('/trusted')
        instance.active = 'operation'
        instance.run = Mock()
        instance.publish = Mock()
        instance.health = Mock()
        return instance

    def test_failed_health_restores_previous_code(self):
        with tempfile.TemporaryDirectory() as directory:
            instance = self.make_worker(directory)
            instance.health.side_effect = [worker.UpdateError('failed'), None]
            with self.assertRaises(worker.UpdateError):
                instance.activate(pathlib.Path('/releases/new'), pathlib.Path('/releases/previous'))
            self.assertEqual(instance.run.call_args_list[-1].args[0], ['/usr/bin/bash', '/trusted/rollback-release.sh', 'previous', '--schema-compatible'])
            self.assertFalse((pathlib.Path(directory) / 'state.json').exists())

    def test_success_records_only_validated_history(self):
        with tempfile.TemporaryDirectory() as directory:
            instance = self.make_worker(directory)
            instance.activate(pathlib.Path('/releases/new'), pathlib.Path('/releases/previous'))
            self.assertEqual(json.loads((pathlib.Path(directory) / 'state.json').read_text()), {'current': 'new', 'previous': 'previous'})

    def test_failed_activation_attempts_recovery(self):
        with tempfile.TemporaryDirectory() as directory:
            instance = self.make_worker(directory)
            instance.run.side_effect = [RuntimeError('activation failed'), None]
            with self.assertRaises(RuntimeError):
                instance.activate(pathlib.Path('/releases/new'), pathlib.Path('/releases/previous'))
            self.assertEqual(instance.run.call_count, 2)

    def test_fixed_argv_has_no_shell(self):
        instance = worker.Worker.__new__(worker.Worker)
        instance.log = None
        with patch.object(worker.subprocess, 'run') as run:
            instance.run(['/usr/bin/bash', '/trusted/stage-release.sh', 'literal'])
            self.assertNotIn('shell', run.call_args.kwargs)
            self.assertTrue(run.call_args.kwargs['check'])


class RequestTests(unittest.TestCase):
    def test_stale_unauthorized_and_wrong_current_requests_never_deploy(self):
        for mutation in ['stale', 'owner', 'current', 'action']:
            with self.subTest(mutation=mutation), tempfile.TemporaryDirectory() as directory:
                root = pathlib.Path(directory)
                for name in ['public', 'logs', 'artifacts']:
                    (root / name).mkdir()
                operation = str(uuid.uuid4())
                request = {'id': operation, 'created_at': int(time.time()), 'actor': 1,
                           'expected_current': 'live', 'action': 'install'}
                if mutation == 'stale':
                    request['created_at'] -= 601
                elif mutation == 'owner':
                    request['actor'] = 99
                elif mutation == 'current':
                    request['expected_current'] = 'different'
                else:
                    request['action'] = '/bin/sh'
                file = root / (operation + '.json')
                file.write_text(json.dumps(request))
                instance = worker.Worker.__new__(worker.Worker)
                instance.root = root
                instance.public = root / 'public'
                instance.web_user = types.SimpleNamespace(pw_uid=file.stat().st_uid)
                instance.config = {'owner_ids': [1]}
                instance.current = Mock(return_value=pathlib.Path('/releases/live'))
                instance.publish = Mock()
                instance.install = Mock()
                instance.rollback = Mock()
                instance.process(file)
                instance.install.assert_not_called()
                instance.rollback.assert_not_called()
                self.assertEqual(instance.publish.call_args.args[1], 'failed')
                self.assertFalse(file.exists())

    def test_download_redirect_never_receives_repository_token(self):
        instance = worker.Worker.__new__(worker.Worker)
        instance.repository = 'owner/repository'
        instance.config = {'token': 'private-token'}
        response = Mock()
        response.__enter__ = Mock(return_value=response)
        response.__exit__ = Mock(return_value=False)
        response.code = 302
        response.headers = {'Location': 'https://release-assets.githubusercontent.com/asset'}
        final = Mock()
        final.__enter__ = Mock(return_value=final)
        final.__exit__ = Mock(return_value=False)
        final.code = 200
        final.read.side_effect = [b'payload', b'']
        opener = Mock()
        opener.open.side_effect = [response, final]
        with patch.object(worker.urllib.request, 'build_opener', return_value=opener):
            self.assertEqual(instance.fetch('/releases/assets/1', asset=True), b'payload')
        first, second = [call.args[0] for call in opener.open.call_args_list]
        self.assertEqual(first.get_header('Authorization'), 'Bearer private-token')
        self.assertIsNone(second.get_header('Authorization'))

    def test_download_redirect_rejects_untrusted_hosts(self):
        instance = worker.Worker.__new__(worker.Worker)
        instance.repository = 'owner/repository'
        instance.config = {}
        response = Mock()
        response.__enter__ = Mock(return_value=response)
        response.__exit__ = Mock(return_value=False)
        response.code = 302
        response.headers = {'Location': 'https://attacker.example/payload'}
        opener = Mock()
        opener.open.return_value = response
        with patch.object(worker.urllib.request, 'build_opener', return_value=opener):
            with self.assertRaises(worker.UpdateError):
                instance.fetch('/releases/assets/1', asset=True)
        self.assertEqual(opener.open.call_count, 1)


if __name__ == '__main__':
    unittest.main(verbosity=2)
