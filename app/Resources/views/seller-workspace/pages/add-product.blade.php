<nav class="msw-crumb" aria-label="{{ trans('admin::seller-workspace.home') }}">
    <a href="{{ route('admin.seller_workspace.overview') }}">{{ trans('admin::seller-workspace.home') }}</a>
    @include('manpleasure-admin::seller-workspace.icon', ['name' => 'right'])
    <a href="{{ route('admin.seller_workspace.products') }}">{{ trans('admin::seller-workspace.pages.products') }}</a>
    @include('manpleasure-admin::seller-workspace.icon', ['name' => 'right'])
    <strong>{{ trans(isset($editing) ? 'admin::seller-workspace.add.edit-title' : 'admin::seller-workspace.pages.add-product') }}</strong>
</nav>
<h1>{{ trans(isset($editing) ? 'admin::seller-workspace.add.edit-title' : 'admin::seller-workspace.titles.add-product') }}</h1>

<div class="msw-add" data-msw-add>
    <div class="msw-add-main">
        <section class="msw-card msw-add-card">
            <h2>{{ trans('admin::seller-workspace.add.basic') }}</h2>

            <div class="msw-add-label"><b>*</b> {{ trans('admin::seller-workspace.add.images') }} <span class="msw-help" title="{{ trans('admin::seller-workspace.add.images-help') }}">@include('manpleasure-admin::seller-workspace.icon', ['name' => 'info'])</span></div>
            <div class="msw-add-drop" id="msw-images" data-msw-invalid-for="images">
                <button type="button" class="msw-add-tile" id="msw-images-add" aria-label="{{ trans('admin::seller-workspace.add.images') }}"><span aria-hidden="true">+</span></button>
            </div>

            <div data-msw-step2 hidden>
                <div class="msw-add-label">{{ trans('admin::seller-workspace.add.promotion') }} <span class="msw-help" title="{{ trans('admin::seller-workspace.add.promotion-help') }}">@include('manpleasure-admin::seller-workspace.icon', ['name' => 'info'])</span></div>
                <div class="msw-add-drop msw-add-drop-half" id="msw-promotion">
                    <button type="button" class="msw-add-tile" id="msw-promotion-add" aria-label="{{ trans('admin::seller-workspace.add.promotion') }}"><span aria-hidden="true">+</span></button>
                    <small>{{ trans('admin::seller-workspace.add.promotion-hint') }}</small>
                </div>
            </div>

            <label class="msw-add-label" for="msw-name"><b>*</b> {{ trans('admin::seller-workspace.add.name') }}</label>
            <div class="msw-add-input">
                <input type="text" id="msw-name" maxlength="255" placeholder="{{ trans('admin::seller-workspace.add.name-placeholder') }}">
                <small id="msw-name-count">0/255</small>
            </div>

            <label class="msw-add-label" for="msw-category"><b>*</b> {{ trans('admin::seller-workspace.add.category') }}</label>
            <div class="msw-add-combo">
                <div class="msw-add-input">
                    <input type="text" id="msw-category" role="combobox" aria-expanded="false" aria-controls="msw-category-list" autocomplete="off" placeholder="{{ trans('admin::seller-workspace.add.category-placeholder') }}">
                    @include('manpleasure-admin::seller-workspace.icon', ['name' => 'down'])
                </div>
                <div class="msw-add-list" id="msw-category-list" role="listbox" hidden></div>
            </div>
            <div class="msw-add-recent" id="msw-recent" hidden><span>{{ trans('admin::seller-workspace.add.recent') }}</span></div>
        </section>

        <div data-msw-step2 hidden>
            <section class="msw-card msw-add-card" id="msw-spec-card" hidden>
                <div class="msw-add-title">
                    <h2>{{ trans('admin::seller-workspace.add.specification') }}</h2>
                    <span>{{ trans('admin::seller-workspace.add.fill-rate') }}</span>
                    <b id="msw-fill-badge">0%</b>
                    <i class="msw-add-bar"><i id="msw-fill-bar"></i></i>
                </div>
                <p class="msw-add-note">{{ trans('admin::seller-workspace.add.specification-hint') }}</p>
                <div class="msw-add-spec" id="msw-spec"></div>
            </section>

            <section class="msw-card msw-add-card" data-msw-price-card>
                <h2>{{ trans('admin::seller-workspace.add.price-title') }}</h2>
                <div class="msw-add-label">{{ trans('admin::seller-workspace.add.variation.title') }}</div>
                <button type="button" class="msw-var-add" id="msw-var-enable">+ {{ trans('admin::seller-workspace.add.variation.enable') }}</button>
                <div id="msw-variations" hidden></div>
                <div id="msw-var-list" hidden>
                    <div class="msw-add-label">{{ trans('admin::seller-workspace.add.variation.list') }}</div>
                    <div class="msw-var-all">
                        <div class="msw-var-all-fields">
                            <label class="msw-add-input"><span data-msw-add-currency></span><input type="text" inputmode="numeric" id="msw-var-all-price" data-msw-money data-column="price" placeholder="{{ trans('admin::seller-workspace.add.variation.price') }}" aria-label="{{ trans('admin::seller-workspace.add.variation.price') }}"></label>
                            <label class="msw-add-input"><input type="text" inputmode="numeric" id="msw-var-all-stock" data-column="stock" placeholder="{{ trans('admin::seller-workspace.add.variation.stock') }}" aria-label="{{ trans('admin::seller-workspace.add.variation.stock') }}"></label>
                            <label class="msw-add-input"><input type="text" id="msw-var-all-sku" maxlength="200" data-column="sku" placeholder="{{ trans('admin::seller-workspace.add.variation.sku') }}" aria-label="{{ trans('admin::seller-workspace.add.variation.sku') }}"></label>
                        </div>
                        <button type="button" class="msw-button msw-primary" id="msw-var-apply" disabled>{{ trans('admin::seller-workspace.add.variation.apply') }}</button>
                    </div>
                    <div class="msw-var-tablewrap"><table class="msw-var-table" id="msw-var-table"></table></div>
                </div>
                <div class="msw-add-label" data-msw-simple><b>*</b> {{ trans('admin::seller-workspace.add.price-stock') }}</div>
                <div class="msw-add-tablewrap"><table class="msw-add-table">
                    <thead><tr>
                        <th data-msw-simple><b>*</b> {{ trans('admin::seller-workspace.add.price') }}</th>
                        <th data-msw-simple>{{ trans('admin::seller-workspace.add.special') }}</th>
                        <th><b>*</b> {{ trans('admin::seller-workspace.add.sku') }}</th>
                        <th data-msw-simple><b>*</b> {{ trans('admin::seller-workspace.add.stock') }}</th>
                        <th id="msw-cost-head" data-msw-simple>{{ trans('admin::seller-workspace.add.cost') }}</th>
                        <th>{{ trans('admin::seller-workspace.add.availability') }}</th>
                    </tr></thead>
                    <tbody><tr>
                        <td data-msw-simple data-label="{{ trans('admin::seller-workspace.add.price') }}"><label class="msw-add-input"><span data-msw-add-currency></span><input type="text" inputmode="numeric" id="msw-price" data-msw-money aria-label="{{ trans('admin::seller-workspace.add.price') }}"></label></td>
                        <td data-msw-simple data-label="{{ trans('admin::seller-workspace.add.special') }}"><button type="button" class="msw-batch-link" id="msw-special-open">{{ trans('admin::seller-workspace.add.special-add') }}</button></td>
                        <td data-label="{{ trans('admin::seller-workspace.add.sku') }}"><label class="msw-add-input"><input type="text" id="msw-sku" maxlength="200" placeholder="{{ trans('admin::seller-workspace.add.sku') }}" aria-label="{{ trans('admin::seller-workspace.add.sku') }}"><small id="msw-sku-count">0/200</small></label></td>
                        <td data-msw-simple data-label="{{ trans('admin::seller-workspace.add.stock') }}"><label class="msw-add-input"><input type="text" inputmode="numeric" id="msw-stock" aria-label="{{ trans('admin::seller-workspace.add.stock') }}"></label></td>
                        <td data-msw-simple data-label="{{ trans('admin::seller-workspace.add.cost') }}" id="msw-cost-cell"><label class="msw-add-input"><span data-msw-add-currency></span><input type="text" inputmode="numeric" id="msw-cost" data-msw-money aria-label="{{ trans('admin::seller-workspace.add.cost') }}"></label></td>
                        <td data-label="{{ trans('admin::seller-workspace.add.availability') }}"><label class="msw-switch-field"><input type="checkbox" id="msw-status" checked aria-label="{{ trans('admin::seller-workspace.add.availability') }}"><span class="msw-switch" aria-hidden="true"></span></label></td>
                    </tr></tbody>
                </table></div>
                <div class="msw-add-flags">
                    <label class="msw-switch-field"><input type="checkbox" id="msw-new"><span class="msw-switch" aria-hidden="true"></span></label> <span>{{ trans('admin::seller-workspace.add.new') }}</span>
                    <label class="msw-switch-field"><input type="checkbox" id="msw-featured"><span class="msw-switch" aria-hidden="true"></span></label> <span>{{ trans('admin::seller-workspace.add.featured') }}</span>
                </div>
            </section>

            <section class="msw-card msw-add-card">
                <h2>{{ trans('admin::seller-workspace.add.description-title') }}</h2>
                <div class="msw-add-label" id="msw-description-label"><b>*</b> {{ trans('admin::seller-workspace.add.description') }}</div>
                <div class="msw-add-editor">
                    <div class="msw-add-tools" role="toolbar" aria-labelledby="msw-description-label">
                        @foreach (['justifyLeft' => 'align-left', 'justifyCenter' => 'align-center', 'justifyRight' => 'align-right', 'insertUnorderedList' => 'list-bullet', 'insertOrderedList' => 'list-number'] as $command => $tool)
                            <button type="button" data-msw-format="{{ $command }}" title="{{ trans('admin::seller-workspace.add.tool-'.$tool) }}" aria-label="{{ trans('admin::seller-workspace.add.tool-'.$tool) }}">@include('manpleasure-admin::seller-workspace.icon', ['name' => $tool])</button>
                        @endforeach
                        <button type="button" id="msw-description-image" title="{{ trans('admin::seller-workspace.add.tool-image') }}" aria-label="{{ trans('admin::seller-workspace.add.tool-image') }}">@include('manpleasure-admin::seller-workspace.icon', ['name' => 'image'])</button>
                    </div>
                    <div id="msw-description" contenteditable="true" role="textbox" aria-multiline="true" aria-labelledby="msw-description-label" data-placeholder="{{ trans('admin::seller-workspace.add.description-placeholder') }}"></div>
                </div>
                <button type="button" class="msw-add-more" id="msw-short-toggle" aria-expanded="false">{{ trans('admin::seller-workspace.add.show-more') }} @include('manpleasure-admin::seller-workspace.icon', ['name' => 'down'])</button>
                <div id="msw-short-row" hidden>
                    <label class="msw-add-label" for="msw-short">{{ trans('admin::seller-workspace.add.short') }}</label>
                    <textarea id="msw-short" class="msw-add-textarea" rows="3" maxlength="255" placeholder="{{ trans('admin::seller-workspace.add.short-placeholder') }}"></textarea>
                </div>
            </section>

            <section class="msw-card msw-add-card">
                <h2>{{ trans('admin::seller-workspace.add.shipping') }}</h2>
                <label class="msw-add-label" for="msw-weight"><b>*</b> {{ trans('admin::seller-workspace.add.weight') }}</label>
                <div class="msw-add-input msw-add-weight"><input type="text" inputmode="decimal" id="msw-weight" placeholder="0.001~300"><span>kg</span></div>
            </section>
        </div>

        <section class="msw-card msw-add-foot">
            <p data-msw-step1>{{ trans('admin::seller-workspace.add.foot-first') }}</p>
            <p data-msw-step2 hidden>@include('manpleasure-admin::seller-workspace.icon', ['name' => 'alert']) {{ trans('admin::seller-workspace.add.foot-second') }}</p>
            <small id="msw-add-message" role="status"></small>
            <div>
                <button type="button" class="msw-button msw-primary" id="msw-next" data-msw-step1 disabled>{{ trans('admin::seller-workspace.add.next') }}</button>
                <button type="button" class="msw-button" id="msw-draft" data-msw-step2 hidden>{{ trans('admin::seller-workspace.add.draft') }}</button>
                <button type="button" class="msw-button msw-primary" id="msw-submit" data-msw-step2 hidden>{{ trans('admin::seller-workspace.add.submit') }}</button>
            </div>
        </section>
    </div>

    <aside class="msw-card msw-add-side" data-msw-step1>
        <h2>{{ trans('admin::seller-workspace.add.category') }}</h2>
        <p>{{ trans('admin::seller-workspace.add.side') }}</p>
    </aside>
</div>

<dialog class="msw-modal msw-modal-special" id="msw-special" aria-labelledby="msw-special-title">
    <h2 class="msw-modal-title" id="msw-special-title">{{ trans('admin::seller-workspace.add.special') }}</h2>
    <button type="button" class="msw-modal-close" data-msw-add-close aria-label="{{ trans('admin::seller-workspace.editor.close') }}">@include('manpleasure-admin::seller-workspace.icon', ['name' => 'close'])</button>
    <div class="msw-modal-body msw-add-special">
        <label>{{ trans('admin::seller-workspace.add.special') }}<input class="msw-alert-input" type="text" inputmode="numeric" id="msw-special-price" data-msw-money></label>
        <label>{{ trans('admin::seller-workspace.add.special-from') }}<input class="msw-alert-input" type="date" id="msw-special-from"></label>
        <label>{{ trans('admin::seller-workspace.add.special-to') }}<input class="msw-alert-input" type="date" id="msw-special-to"></label>
    </div>
    <div class="msw-modal-foot">
        <button type="button" class="msw-button msw-modal-cancel" data-msw-add-close>{{ trans('admin::seller-workspace.editor.cancel') }}</button>
        <button type="button" class="msw-button msw-primary" id="msw-special-ok">{{ trans('admin::seller-workspace.editor.ok') }}</button>
    </div>
</dialog>

<dialog class="msw-modal msw-modal-price" id="msw-var-special" aria-labelledby="msw-var-special-title">
    <form method="dialog" id="msw-var-special-form">
        <input type="hidden" name="special_price_from" value="">
        <input type="hidden" name="special_price_to" value="">
        <h2 class="msw-modal-title" id="msw-var-special-title">{{ trans('admin::seller-workspace.actions.edit-price') }}</h2>
        <button type="button" class="msw-modal-close" data-msw-close aria-label="{{ trans('admin::seller-workspace.editor.close') }}">@include('manpleasure-admin::seller-workspace.icon', ['name' => 'close'])</button>
        <div class="msw-modal-body">
            <table class="msw-modal-table">
                <thead><tr>
                    <th>{{ trans('admin::seller-workspace.editor.product-info') }}</th>
                    <th>{{ trans('admin::seller-workspace.editor.retail') }}</th>
                    <th>{{ trans('admin::seller-workspace.editor.daily') }}</th>
                </tr></thead>
                <tbody><tr>
                    <td class="msw-modal-info"><div class="msw-product-info"><i aria-hidden="true" id="msw-var-special-picture"></i><span class="msw-var-special-info"><span id="msw-var-special-info"></span><span id="msw-var-special-sku"></span></span></div></td>
                    <td data-label="{{ trans('admin::seller-workspace.editor.retail') }}">
                        <label class="msw-money"><span data-msw-currency></span><input type="text" inputmode="numeric" name="price" data-msw-money required aria-label="{{ trans('admin::seller-workspace.editor.retail') }}"></label>
                    </td>
                    <td data-label="{{ trans('admin::seller-workspace.editor.daily') }}">
                        <div class="msw-money-row">
                            <label class="msw-money"><span data-msw-currency></span><input type="text" inputmode="numeric" name="special_price" data-msw-money aria-label="{{ trans('admin::seller-workspace.editor.daily') }}"></label>
                            <button type="button" class="msw-clock" data-msw-date-open title="{{ trans('admin::seller-workspace.editor.date-title') }}" aria-label="{{ trans('admin::seller-workspace.editor.date-title') }}">@include('manpleasure-admin::seller-workspace.icon', ['name' => 'clock'])</button>
                        </div>
                    </td>
                </tr></tbody>
            </table>
        </div>
        <div class="msw-modal-foot">
            <small data-msw-message role="status"></small>
            <button type="button" class="msw-button msw-modal-cancel" data-msw-close>{{ trans('admin::seller-workspace.editor.cancel') }}</button>
            <button type="submit" class="msw-button msw-primary">{{ trans('admin::seller-workspace.editor.ok') }}</button>
        </div>

        <div class="msw-date" data-msw-date hidden data-msw-prev-year="{{ trans('admin::seller-workspace.editor.prev-year') }}" data-msw-prev-month="{{ trans('admin::seller-workspace.editor.prev-month') }}" data-msw-next-month="{{ trans('admin::seller-workspace.editor.next-month') }}" data-msw-next-year="{{ trans('admin::seller-workspace.editor.next-year') }}">
            <button type="button" class="msw-date-close" data-msw-date-close aria-label="{{ trans('admin::seller-workspace.editor.close') }}">@include('manpleasure-admin::seller-workspace.icon', ['name' => 'close'])</button>
            <b>{{ trans('admin::seller-workspace.editor.date-title') }}</b>
            <div class="msw-date-select">
                <button type="button" data-msw-date-mode aria-expanded="false" data-msw-placeholder="{{ trans('admin::seller-workspace.editor.date-set') }}">
                    <span></span>
                    @include('manpleasure-admin::seller-workspace.icon', ['name' => 'down'])
                </button>
                <div class="msw-date-options" data-msw-date-options hidden>
                    <button type="button" data-msw-date-pick="long">{{ trans('admin::seller-workspace.editor.long-term') }}</button>
                    <button type="button" data-msw-date-pick="set">{{ trans('admin::seller-workspace.editor.set-date') }}</button>
                </div>
            </div>
            <div class="msw-date-range" data-msw-date-range hidden>
                <button type="button" data-msw-date-field>
                    <span data-msw-date-from data-msw-placeholder="{{ trans('admin::seller-workspace.editor.start') }}"></span>
                    <i>-</i>
                    <span data-msw-date-to data-msw-placeholder="{{ trans('admin::seller-workspace.editor.end') }}"></span>
                    @include('manpleasure-admin::seller-workspace.icon', ['name' => 'calendar'])
                </button>
                <div class="msw-calendar" data-msw-calendar hidden>
                    <div class="msw-calendar-head" data-msw-calendar-head></div>
                    <div class="msw-calendar-grid" data-msw-calendar-grid></div>
                    <div class="msw-calendar-foot"><button type="button" class="msw-button msw-primary" data-msw-calendar-ok>{{ trans('admin::seller-workspace.editor.ok') }}</button></div>
                </div>
            </div>
            <div class="msw-date-foot">
                <button type="button" class="msw-button msw-modal-cancel" data-msw-date-close>{{ trans('admin::seller-workspace.editor.cancel') }}</button>
                <button type="button" class="msw-button msw-primary" data-msw-date-confirm>{{ trans('admin::seller-workspace.editor.confirm') }}</button>
            </div>
        </div>
    </form>
</dialog>

<script type="application/json" id="msw-entry">{!! json_encode([
    'categories' => $entry['categories'],
    'families' => $entry['families'],
    'productTypes' => $entry['productTypes'],
    'numeric' => $entry['numeric'],
    'currency' => preg_replace('/[\d.,\s]/u', '', core()->formatBasePrice(0)),
    'csrf' => csrf_token(),
    'product' => $editing ?? null,
    'urls' => [
        'store' => isset($editing) ? route('admin.catalog.product_entry.update', $editing['id']) : $entry['urls']['store'],
        'sku' => $entry['urls']['sku'],
        'descriptionImage' => $entry['urls']['descriptionImage'],
        'done' => [
            0 => $back ?? route('admin.seller_workspace.products', ['tab' => 'inactive']),
            1 => $back ?? route('admin.seller_workspace.products', ['tab' => 'active']),
            'draft' => $back ?? route('admin.seller_workspace.products', ['tab' => 'draft']),
        ],
    ],
    'text' => [
        'failed' => trans('admin::product-entry.save-failed'),
        'badImage' => trans('admin::seller-workspace.add.bad-image'),
        'imageUploading' => trans('admin::seller-workspace.add.image-uploading'),
        'imageFailed' => trans('admin::seller-workspace.add.image-failed'),
        'tooMany' => trans('admin::seller-workspace.add.too-many'),
        'remove' => trans('admin::seller-workspace.add.remove-image'),
        'yes' => trans('admin::seller-workspace.add.yes'),
        'select' => trans('admin::seller-workspace.add.select'),
        'specialAdd' => trans('admin::seller-workspace.add.special-add'),
        'noMatch' => trans('admin::seller-workspace.add.no-match'),
        'showMore' => trans('admin::seller-workspace.add.show-more'),
        'showLess' => trans('admin::seller-workspace.add.show-less'),
        'variation' => [
            'heading' => trans('admin::seller-workspace.add.variation.heading', ['number' => ':number']),
            'column' => trans('admin::seller-workspace.add.variation.column', ['number' => ':number']),
            'add' => trans('admin::seller-workspace.add.variation.add', ['number' => ':number']),
            'namePlaceholder' => trans('admin::seller-workspace.add.variation.name-placeholder'),
            'options' => trans('admin::seller-workspace.add.variation.options'),
            'optionPlaceholder' => trans('admin::seller-workspace.add.variation.option-placeholder'),
            'input' => trans('admin::seller-workspace.add.variation.input'),
            'explanation' => trans('admin::seller-workspace.add.variation.explanation'),
            'price' => trans('admin::seller-workspace.add.variation.price'),
            'stock' => trans('admin::seller-workspace.add.variation.stock'),
            'sku' => trans('admin::seller-workspace.add.variation.sku'),
            'remove' => trans('admin::seller-workspace.add.variation.remove'),
            'removeOption' => trans('admin::seller-workspace.add.variation.remove-option'),
            'moveOption' => trans('admin::seller-workspace.add.variation.move-option'),
            'addImage' => trans('admin::seller-workspace.add.variation.add-image'),
            'spaces' => trans('admin::seller-workspace.add.variation.spaces'),
            'special' => trans('admin::seller-workspace.add.special'),
            'sellerSku' => trans('admin::seller-workspace.add.sku'),
            'specialLower' => trans('admin::seller-workspace.add.variation.special-lower'),
        ],
    ],
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
<script>
    (function () {
        const entry = JSON.parse(document.getElementById('msw-entry').textContent);
        const byId = (id) => document.getElementById(id);
        const MAX_IMAGES = 9;
        const MAX_IMAGE_BYTES = 5242880;
        const IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
        const MAX_OPTIONS = 20;
        const RECENT_KEY = 'msw-recent-categories';
        const state = { images: [], promotion: null, category: null, fields: [], values: {}, skuTouched: false, busy: false, step: 1, variations: [], rows: {}, uid: 0, dragging: null, imageTarget: null, descriptionRange: null };
        const subcategories = entry.categories.flatMap((main) => main.children.map((sub) => ({ ...sub, family: main.family, path: main.name + ' > ' + sub.name })));
        const message = byId('msw-add-message');

        const slugify = (text) => text.toLowerCase().normalize('NFKD').replace(/[̀-ͯ]/g, '').replace(/&/g, ' and ').replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 100);

        function showMessage(text) {
            message.textContent = text;
        }

        function clearErrors() {
            showMessage('');
            document.querySelectorAll('.msw-add-invalid').forEach((node) => node.classList.remove('msw-add-invalid'));
        }

        function picker(multiple, onPick) {
            const input = document.createElement('input');

            input.type = 'file';
            input.multiple = multiple;
            input.accept = IMAGE_TYPES.join(',');
            input.hidden = true;
            input.addEventListener('change', () => {
                onPick(Array.from(input.files));
                input.value = '';
            });
            document.body.appendChild(input);

            return input;
        }

        function validImage(file) {
            if (IMAGE_TYPES.includes(file.type) && file.size <= MAX_IMAGE_BYTES) {
                return true;
            }

            showMessage(entry.text.badImage);

            return false;
        }

        function imageTile(image, onRemove) {
            const tile = document.createElement('button');

            tile.type = 'button';
            tile.className = 'msw-add-thumb';
            tile.title = entry.text.remove;
            tile.setAttribute('aria-label', entry.text.remove);
            tile.style.backgroundImage = 'url("' + image.url + '")';
            tile.addEventListener('click', () => {
                URL.revokeObjectURL(image.url);
                onRemove();
                renderImages();
                refresh();
            });

            return tile;
        }

        function addImages(files) {
            clearErrors();

            files.forEach((file) => {
                if (state.images.length >= MAX_IMAGES) {
                    showMessage(entry.text.tooMany);
                } else if (validImage(file)) {
                    state.images.push({ file, url: URL.createObjectURL(file) });
                }
            });

            renderImages();
            refresh();
        }

        function renderImages() {
            const holder = byId('msw-images');
            const add = byId('msw-images-add');
            const promotion = byId('msw-promotion');

            holder.querySelectorAll('.msw-add-thumb').forEach((node) => node.remove());
            state.images.forEach((image, index) => holder.insertBefore(imageTile(image, () => state.images.splice(index, 1)), add));
            add.hidden = state.images.length >= MAX_IMAGES;

            promotion.querySelectorAll('.msw-add-thumb').forEach((node) => node.remove());
            byId('msw-promotion-add').hidden = Boolean(state.promotion);

            if (state.promotion) {
                promotion.prepend(imageTile(state.promotion, () => {
                    state.promotion = null;
                }));
            }
        }

        function readRecent() {
            try {
                return JSON.parse(localStorage.getItem(RECENT_KEY)) || [];
            } catch (error) {
                return [];
            }
        }

        function rememberCategory(id) {
            try {
                localStorage.setItem(RECENT_KEY, JSON.stringify([id, ...readRecent().filter((item) => item !== id)].slice(0, 3)));
            } catch (error) {
                return;
            }
        }

        function renderRecent() {
            const holder = byId('msw-recent');
            const recent = readRecent().map((id) => subcategories.find((item) => item.id === id)).filter(Boolean);

            holder.querySelectorAll('button').forEach((node) => node.remove());
            holder.hidden = recent.length === 0;

            recent.forEach((item) => {
                const chip = document.createElement('button');

                chip.type = 'button';
                chip.textContent = item.name;
                chip.addEventListener('click', () => chooseCategory(item));
                holder.appendChild(chip);
            });
        }

        function renderCategoryList(term) {
            const list = byId('msw-category-list');
            const needle = term.trim().toLowerCase();
            const matches = subcategories.filter((item) => item.path.toLowerCase().includes(needle));

            list.textContent = '';

            matches.forEach((item) => {
                const option = document.createElement('button');

                option.type = 'button';
                option.role = 'option';
                option.textContent = item.path;
                option.addEventListener('click', () => chooseCategory(item));
                list.appendChild(option);
            });

            if (! matches.length) {
                const none = document.createElement('p');

                none.textContent = entry.text.noMatch;
                list.appendChild(none);
            }
        }

        function setCategoryList(open) {
            byId('msw-category-list').hidden = ! open;
            byId('msw-category').setAttribute('aria-expanded', String(open));
        }

        function visibleFields() {
            const names = state.variations.map((variation) => variation.name.trim().toLowerCase());

            return state.fields.filter((field) => ! names.includes(field.label.toLowerCase()));
        }

        function refreshFields() {
            const shown = [...byId('msw-spec').querySelectorAll('[data-field]')].map((control) => control.dataset.field).join();

            if (shown !== visibleFields().map((field) => field.code).join()) {
                renderFields();
            }

            refresh();
        }

        function fieldControl(field) {
            let control;

            if (field.type === 'select' || field.type === 'boolean') {
                control = document.createElement('select');
                control.appendChild(new Option(entry.text.select, ''));

                if (field.type === 'boolean') {
                    control.appendChild(new Option(entry.text.yes, '1'));
                } else {
                    field.options.forEach((item) => control.appendChild(new Option(item.label, item.value)));
                }
            } else {
                control = document.createElement('input');
                control.type = field.type === 'date' ? 'date' : 'text';

                if (entry.numeric.includes(field.code)) {
                    control.inputMode = 'decimal';
                }
            }

            control.dataset.field = field.code;
            control.value = state.values[field.code] ?? '';
            control.setAttribute('aria-label', field.label);
            control.addEventListener('input', () => {
                state.values[field.code] = control.value;
                refresh();
            });

            return control;
        }

        function renderFields() {
            const holder = byId('msw-spec');

            holder.textContent = '';
            byId('msw-spec-card').hidden = visibleFields().length === 0;

            visibleFields().forEach((field) => {
                const row = document.createElement('div');
                const label = document.createElement('div');
                const box = document.createElement('div');

                label.className = 'msw-add-label';
                label.append(field.label);
                box.className = 'msw-add-input';
                box.appendChild(fieldControl(field));
                row.append(label, box);
                holder.appendChild(row);
            });
        }

        async function suggestSku() {
            if (state.skuTouched || ! state.category) {
                return;
            }

            const response = await fetch(entry.urls.sku + '?category_id=' + state.category.id, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });

            if (response.ok && ! state.skuTouched) {
                const data = await response.json();

                byId('msw-sku').value = data.sku || '';
                refresh();
            }
        }

        function chooseCategory(item) {
            const family = entry.families[item.family];

            state.category = item;
            state.fields = family?.fields ?? [];
            byId('msw-category').value = item.path;
            byId('msw-cost-head').hidden = ! family?.has_cost;
            byId('msw-cost-cell').hidden = ! family?.has_cost;

            setCategoryList(false);
            rememberCategory(item.id);
            renderRecent();
            renderFields();
            suggestSku();
            refresh();
        }

        const variationIcons = {
            close: @json(view('manpleasure-admin::seller-workspace.icon', ['name' => 'close'])->render()),
            trash: @json(view('manpleasure-admin::seller-workspace.icon', ['name' => 'trash'])->render()),
        };

        function nextUid() {
            state.uid += 1;

            return state.uid;
        }

        function newOption(label = '', note = '', image = null) {
            return { uid: nextUid(), label, note, image, file: null };
        }

        function newVariation(name = '') {
            return { uid: nextUid(), name, options: [newOption()] };
        }

        function filledOptions(variation) {
            return variation.options.filter((option) => option.label !== '');
        }

        function variationText(text, number) {
            return text.replace(':number', number);
        }

        function hasEdgeSpace(value) {
            return value !== value.trim();
        }

        function bindMoney(input) {
            input.addEventListener('input', () => {
                const digits = input.value.replace(/\D/g, '').replace(/^0+/, '').slice(0, 12);

                input.value = digits ? (Number(digits) / 100).toFixed(2) : '';
            });
        }

        function textInput(value, placeholder, maxLength, onInput) {
            const input = document.createElement('input');

            input.type = 'text';
            input.value = value;
            input.placeholder = placeholder;
            input.maxLength = maxLength;
            input.addEventListener('input', () => onInput(input));

            return input;
        }

        function spaceHint(box, value) {
            const bad = hasEdgeSpace(value);
            let hint = box.nextElementSibling?.classList.contains('msw-var-hint') ? box.nextElementSibling : null;

            box.classList.toggle('msw-add-invalid', bad);

            if (bad && ! hint) {
                hint = document.createElement('small');
                hint.className = 'msw-var-hint';
                hint.textContent = entry.text.variation.spaces;
                box.after(hint);
            } else if (! bad && hint) {
                hint.remove();
            }
        }

        function moveOption(variation, from, to) {
            const [item] = variation.options.splice(from, 1);

            variation.options.splice(to, 0, item);
            renderVariations();
        }

        function optionRow(variation, option, position) {
            const row = document.createElement('div');
            const fields = document.createElement('div');
            const spare = option.label === '' && position === variation.options.length - 1;

            row.className = 'msw-var-option';
            fields.className = 'msw-var-fields';
            row.dataset.option = option.uid;

            const label = textInput(option.label, position === 0 ? entry.text.variation.optionPlaceholder : entry.text.variation.input, 60, (input) => {
                option.label = input.value;
                spaceHint(fields, input.value);

                if (position === variation.options.length - 1 && input.value !== '' && variation.options.length < MAX_OPTIONS) {
                    variation.options.push(newOption());
                    renderOptions(variation, input);
                }

                renderVariationTable();
                refreshFields();
            });
            const note = textInput(option.note, entry.text.variation.explanation, 120, (input) => {
                option.note = input.value;
            });

            label.setAttribute('aria-label', entry.text.variation.options);
            label.dataset.mswKey = 'option-' + option.uid;
            note.setAttribute('aria-label', entry.text.variation.explanation);
            label.addEventListener('focus', () => highlight('[data-option-cell~="' + option.uid + '"]'));
            label.addEventListener('blur', () => highlight(null));
            fields.append(label, note);
            row.appendChild(fields);

            const move = document.createElement('button');
            const remove = document.createElement('button');

            move.type = 'button';
            move.className = 'msw-var-tool';
            move.textContent = '\u2725';
            move.title = entry.text.variation.moveOption;
            move.setAttribute('aria-label', entry.text.variation.moveOption);
            move.draggable = ! spare;
            move.addEventListener('dragstart', (event) => {
                state.dragging = { variation: variation.uid, from: position };
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', String(position));
            });
            move.addEventListener('keydown', (event) => {
                const to = position + (event.key === 'ArrowRight' ? 1 : (event.key === 'ArrowLeft' ? -1 : 0));
                const last = filledOptions(variation).length - 1;

                if (! spare && to !== position && to >= 0 && to <= last) {
                    event.preventDefault();
                    moveOption(variation, position, to);
                    document.querySelector('[data-option="' + option.uid + '"] .msw-var-tool')?.focus();
                }
            });

            remove.type = 'button';
            remove.className = 'msw-var-tool';
            remove.innerHTML = variationIcons.trash;
            remove.title = entry.text.variation.removeOption;
            remove.setAttribute('aria-label', entry.text.variation.removeOption);
            remove.disabled = variation.options.length === 1;
            remove.addEventListener('click', () => {
                variation.options.splice(position, 1);

                if (variation.options[variation.options.length - 1].label !== '') {
                    variation.options.push(newOption());
                }

                renderVariations();
            });

            row.append(move, remove);

            if (! spare) {
                row.addEventListener('dragover', (event) => {
                    if (state.dragging?.variation === variation.uid) {
                        event.preventDefault();
                    }
                });
                row.addEventListener('drop', (event) => {
                    event.preventDefault();

                    if (state.dragging?.variation === variation.uid && state.dragging.from !== position) {
                        moveOption(variation, state.dragging.from, position);
                    }

                    state.dragging = null;
                });
            }

            return row;
        }

        function renderOptions(variation, focused) {
            const holder = document.querySelector('[data-variation="' + variation.uid + '"] .msw-var-options');
            const focusKey = focused?.dataset.mswKey;
            const caret = focused?.selectionStart;

            holder.textContent = '';
            variation.options.forEach((option, position) => holder.appendChild(optionRow(variation, option, position)));

            if (focusKey) {
                const again = holder.querySelector('[data-msw-key="' + focusKey + '"]');

                again.focus();
                again.setSelectionRange(caret, caret);
            }
        }

        function renderVariations() {
            const holder = byId('msw-variations');
            const list = byId('msw-var-list');
            const active = state.variations.length > 0;

            byId('msw-var-enable').hidden = active;
            holder.hidden = ! active;
            list.hidden = ! active;
            document.querySelector('[data-msw-price-card]').classList.toggle('is-variants', active);
            holder.textContent = '';

            state.variations.forEach((variation, index) => {
                const box = document.createElement('div');
                const head = document.createElement('div');
                const close = document.createElement('button');
                const nameBox = document.createElement('div');
                const optionsLabel = document.createElement('div');
                const options = document.createElement('div');

                box.className = 'msw-var-box';
                box.dataset.variation = variation.uid;
                head.className = 'msw-add-label';
                head.textContent = variationText(entry.text.variation.heading, index + 1);
                close.type = 'button';
                close.className = 'msw-var-close';
                close.innerHTML = variationIcons.close;
                close.title = entry.text.variation.remove;
                close.setAttribute('aria-label', entry.text.variation.remove);
                close.addEventListener('click', () => {
                    state.variations.splice(index, 1);
                    renderVariations();
                });
                nameBox.className = 'msw-add-input msw-var-name';
                nameBox.appendChild(textInput(variation.name, entry.text.variation.namePlaceholder, 40, (input) => {
                    variation.name = input.value;
                    spaceHint(nameBox, input.value);
                    renderVariationTable();
                    refreshFields();
                }));
                nameBox.firstChild.setAttribute('aria-label', head.textContent);
                nameBox.firstChild.dataset.mswKey = 'name-' + variation.uid;
                optionsLabel.className = 'msw-add-label';
                optionsLabel.textContent = entry.text.variation.options;
                options.className = 'msw-var-options';
                box.append(close, head, nameBox, optionsLabel, options);
                holder.appendChild(box);
                renderOptions(variation);
            });

            if (state.variations.length === 1) {
                const box = document.createElement('div');
                const add = document.createElement('button');

                box.className = 'msw-var-box';
                add.type = 'button';
                add.className = 'msw-var-add';
                add.textContent = '+ ' + variationText(entry.text.variation.add, 2);
                add.addEventListener('click', () => {
                    state.variations.push(newVariation());
                    renderVariations();
                    document.querySelector('[data-variation="' + state.variations[1].uid + '"] input')?.focus();
                });
                box.appendChild(add);
                holder.appendChild(box);
            }

            renderVariationTable();
            refreshFields();
        }

        function rowKey(first, second) {
            return first.uid + '-' + (second?.uid ?? 0);
        }

        function rowFor(key) {
            state.rows[key] = state.rows[key] ?? { price: '', stock: '0', sku: '', special_price: '', special_price_from: '', special_price_to: '' };

            return state.rows[key];
        }

        function optionImage(option) {
            const tile = document.createElement('button');

            tile.type = 'button';
            tile.className = 'msw-var-image' + (option.image ? ' has-image' : '');
            tile.title = option.image ? entry.text.remove : entry.text.variation.addImage;
            tile.setAttribute('aria-label', tile.title);

            if (option.image) {
                tile.style.backgroundImage = 'url("' + option.image + '")';
            } else {
                tile.textContent = '+';
            }

            tile.addEventListener('click', () => {
                if (option.image) {
                    option.image = null;
                    option.file = null;
                    renderVariationTable();

                    return;
                }

                state.imageTarget = option;
                optionImagePicker.click();
            });

            return tile;
        }

        function rowInput(row, field, label, key) {
            const box = document.createElement('label');
            const input = document.createElement('input');

            box.className = 'msw-add-input';
            input.type = 'text';
            input.value = row[field];
            input.setAttribute('aria-label', label);
            input.dataset.mswKey = key;
            input.dataset.rowField = field;

            if (field === 'price') {
                const currency = document.createElement('span');

                currency.textContent = entry.currency;
                box.appendChild(currency);
                input.inputMode = 'numeric';
                bindMoney(input);
            } else if (field === 'stock') {
                input.inputMode = 'numeric';
            } else {
                input.maxLength = 200;
            }

            input.addEventListener('input', () => {
                row[field] = input.value;
            });
            box.appendChild(input);

            return box;
        }

        function dayFirst(date) {
            return date ? date.split('-').reverse().join('-') : '';
        }

        function specialCell(row, key, labels, image, suffix) {
            const button = document.createElement('button');

            button.type = 'button';
            button.className = 'msw-batch-link';
            button.dataset.mswKey = key;
            button.textContent = row.special_price ? entry.currency + ' ' + row.special_price : entry.text.specialAdd;
            button.setAttribute('aria-label', entry.text.variation.special + ': ' + labels);
            button.addEventListener('click', () => openSpecial(row, labels, image, suffix));

            if (! row.special_price || ! (row.special_price_from || row.special_price_to)) {
                return button;
            }

            const box = document.createElement('div');
            const dates = document.createElement('small');

            dates.className = 'msw-var-special-dates';
            dates.textContent = (dayFirst(row.special_price_from) + ' ~ ' + dayFirst(row.special_price_to)).trim();
            box.append(dates, button);

            return box;
        }

        function openSpecial(row, labels, image, suffix) {
            const dialog = byId('msw-var-special');
            const form = byId('msw-var-special-form');
            const pop = form.querySelector('[data-msw-date]');

            state.specialRow = row;
            dialog.showModal();
            form.elements.price.value = row.price;
            form.elements.special_price.value = row.special_price;
            form.elements.special_price_from.value = row.special_price_from;
            form.elements.special_price_to.value = row.special_price_to;
            form.querySelectorAll('[data-msw-currency]').forEach((node) => {
                node.textContent = entry.currency;
            });
            const picture = document.createElement(image ? 'img' : 'i');
            const sku = row.sku.trim();

            picture.id = 'msw-var-special-picture';
            picture.setAttribute('aria-hidden', 'true');

            if (image) {
                picture.src = image;
                picture.alt = '';
            }

            byId('msw-var-special-picture').replaceWith(picture);
            byId('msw-var-special-info').textContent = labels;
            byId('msw-var-special-sku').textContent = entry.text.variation.sellerSku + ': ' + (sku && ! sku.endsWith('-') ? sku : (sku || byId('msw-sku').value.trim() + '-') + suffix);
            form.querySelector('[data-msw-message]').textContent = '';
            pop.hidden = true;
            pop.dataset.mswConfirmed = row.special_price_from || row.special_price_to ? 'set' : (row.special_price ? 'long' : '');
            form.elements.price.focus();
        }

        function renderVariationTable() {
            const table = byId('msw-var-table');
            const [first, second] = state.variations;

            if (! first) {
                table.textContent = '';

                return;
            }

            const firstOptions = filledOptions(first).length ? filledOptions(first) : [null];
            const secondOptions = second ? (filledOptions(second).length ? filledOptions(second) : [null]) : [undefined];
            const head = document.createElement('tr');
            const body = document.createElement('tbody');
            const thead = document.createElement('thead');
            const titles = [first.name.trim() || variationText(entry.text.variation.column, 1)];

            if (second) {
                titles.push(second.name.trim() || variationText(entry.text.variation.column, 2));
            }

            titles.forEach((title) => {
                const cell = document.createElement('th');

                cell.textContent = title;
                head.appendChild(cell);
            });

            [[entry.text.variation.price, true, 'price'], [entry.text.variation.special, false, 'special'], [entry.text.variation.stock, true, 'stock'], [entry.text.variation.sku, false, 'sku']].forEach(([title, required, field]) => {
                const cell = document.createElement('th');

                cell.dataset.columnCell = field;

                if (required) {
                    const star = document.createElement('b');

                    star.textContent = '*';
                    cell.append(star, ' ');
                }

                cell.append(title);
                head.appendChild(cell);
            });

            let rowIndex = 0;

            firstOptions.forEach((one) => {
                secondOptions.forEach((two, position) => {
                    const tr = document.createElement('tr');

                    if (position === 0) {
                        const cell = document.createElement('td');
                        const name = document.createElement('div');

                        cell.rowSpan = secondOptions.length;
                        cell.className = 'msw-var-first';
                        name.textContent = one?.label ?? '';
                        cell.appendChild(name);

                        if (one) {
                            cell.dataset.optionCell = String(one.uid);
                            cell.appendChild(optionImage(one));
                        }

                        tr.appendChild(cell);
                    }

                    if (second) {
                        const cell = document.createElement('td');

                        cell.className = 'msw-var-second';
                        cell.textContent = two?.label ?? '';

                        if (two) {
                            cell.dataset.optionCell = String(two.uid);
                        }

                        tr.appendChild(cell);
                    }

                    const row = rowFor(rowKey(one ?? { uid: 0 }, two));
                    const labels = [one?.label, two?.label].filter(Boolean).join(' / ');
                    const suffix = [one?.label, two?.label].filter(Boolean).map((label) => label.toLowerCase().replace(/[^\p{L}\p{N}]+/gu, '-').replace(/^-+|-+$/g, '')).join('-');

                    [['price', entry.text.variation.price], ['special', entry.text.variation.special], ['stock', entry.text.variation.stock], ['sku', entry.text.variation.sku]].forEach(([field, label]) => {
                        const cell = document.createElement('td');

                        cell.dataset.label = label;
                        cell.dataset.columnCell = field;
                        cell.appendChild(field === 'special'
                            ? specialCell(row, 'variation_rows.' + rowIndex + '.special_price', labels, one?.image, suffix)
                            : rowInput(row, field, label, 'variation_rows.' + rowIndex + '.' + field));
                        tr.appendChild(cell);
                    });

                    body.appendChild(tr);
                    rowIndex += 1;
                });
            });

            thead.appendChild(head);
            table.textContent = '';
            table.append(thead, body);
        }

        function highlight(selector) {
            document.querySelectorAll('.is-linked').forEach((node) => node.classList.remove('is-linked'));

            if (selector) {
                document.querySelectorAll(selector).forEach((node) => node.classList.add('is-linked'));
            }
        }

        function refreshApply() {
            byId('msw-var-apply').disabled = ! ['price', 'stock', 'sku'].some((field) => byId('msw-var-all-' + field).value.trim() !== '');
        }

        function variationPayload() {
            const variations = state.variations.map((variation) => ({
                name: variation.name,
                options: filledOptions(variation).map((option) => ({ label: option.label, note: option.note, keep_image: Boolean(option.image && ! option.file) })),
            }));
            const rows = [];
            const [first, second] = state.variations;

            filledOptions(first).forEach((one, a) => {
                (second ? filledOptions(second) : [undefined]).forEach((two, b) => {
                    const row = rowFor(rowKey(one, two));

                    rows.push({ options: second ? [a, b] : [a], price: row.price, special_price: row.special_price, special_price_from: row.special_price ? row.special_price_from : '', special_price_to: row.special_price ? row.special_price_to : '', stock: row.stock, sku: row.sku });
                });
            });

            return { variations, rows };
        }

        function loadVariations(data) {
            state.variations = data.variations.map((saved) => {
                const variation = newVariation(saved.name);

                variation.options = saved.options.map((option) => newOption(option.label, option.note, option.image));
                variation.options.push(newOption());

                return variation;
            });

            data.rows.forEach((row) => {
                const one = state.variations[0].options[row.options[0]];
                const two = state.variations[1]?.options[row.options[1]];

                if (one) {
                    state.rows[rowKey(one, two)] = { price: row.price, stock: String(row.stock), sku: row.sku, special_price: row.special_price ?? '', special_price_from: row.special_price_from ?? '', special_price_to: row.special_price_to ?? '' };
                }
            });

            renderVariations();
        }

        function descriptionText() {
            return byId('msw-description').innerText.trim();
        }

        function descriptionFilled() {
            return descriptionText() !== '' || Boolean(byId('msw-description').querySelector('img'));
        }

        function rememberDescriptionPlace() {
            const selection = window.getSelection();

            if (selection.rangeCount && byId('msw-description').contains(selection.getRangeAt(0).commonAncestorContainer)) {
                state.descriptionRange = selection.getRangeAt(0).cloneRange();
            }
        }

        function placeDescriptionImage(url) {
            const editor = byId('msw-description');
            const block = document.createElement('div');
            const image = document.createElement('img');
            const range = state.descriptionRange && editor.contains(state.descriptionRange.commonAncestorContainer) ? state.descriptionRange : null;

            image.src = url;
            image.alt = byId('msw-name').value.trim();
            image.style.maxWidth = '100%';
            image.style.height = 'auto';
            block.appendChild(image);

            if (range) {
                range.deleteContents();
                range.insertNode(block);
            } else {
                editor.appendChild(block);
            }

            state.descriptionRange = document.createRange();
            state.descriptionRange.setStartAfter(block);
            state.descriptionRange.collapse(true);
            window.getSelection().removeAllRanges();
            window.getSelection().addRange(state.descriptionRange.cloneRange());
        }

        async function uploadDescriptionImages(files) {
            clearErrors();

            if (! files.length || ! files.every(validImage)) {
                return;
            }

            byId('msw-description-image').disabled = true;
            showMessage(entry.text.imageUploading);

            try {
                for (const file of files) {
                    const data = new FormData();

                    data.append('image', file);

                    const response = await fetch(entry.urls.descriptionImage, {
                        method: 'POST',
                        body: data,
                        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': entry.csrf },
                        credentials: 'same-origin',
                    });
                    const body = await response.json().catch(() => ({}));

                    if (! response.ok || ! body.url) {
                        throw new Error(Object.values(body.errors ?? {}).flat()[0] ?? body.message ?? entry.text.imageFailed);
                    }

                    placeDescriptionImage(body.url);
                }

                showMessage('');
            } catch (error) {
                showMessage(error.message || entry.text.imageFailed);
            }

            byId('msw-description-image').disabled = false;
            refresh();
        }

        function refresh() {
            const fields = visibleFields();
            const filled = fields.filter((field) => (state.values[field.code] ?? '') !== '').length;
            const rate = fields.length ? Math.round(filled / fields.length * 100) : 0;
            const canSave = ! state.busy && Boolean(state.category);

            byId('msw-name-count').textContent = byId('msw-name').value.length + '/255';
            byId('msw-sku-count').textContent = byId('msw-sku').value.length + '/200';
            byId('msw-fill-badge').textContent = rate + '%';
            byId('msw-fill-badge').classList.toggle('is-done', rate === 100);
            byId('msw-fill-bar').style.width = rate + '%';
            byId('msw-next').disabled = ! (state.images.length && byId('msw-name').value.trim() && state.category);
            byId('msw-draft').disabled = ! canSave;
            byId('msw-submit').disabled = ! canSave;
            byId('msw-description').classList.toggle('is-empty', ! descriptionFilled());
        }

        function buildPayload(status, urlKey) {
            const data = new FormData();
            const add = (key, value) => {
                if (value !== undefined && value !== null && value !== '') {
                    data.append(key, value);
                }
            };
            const description = descriptionText();
            const short = byId('msw-short').value.trim() || description.replace(/\s+/g, ' ').slice(0, 255);
            const unchanged = entry.product && state.category?.id === entry.product.category_id && entry.product.mp_product_type;
            const type = unchanged ? { value: entry.product.mp_product_type } : (entry.productTypes.find((item) => item.label === state.category?.type) ?? entry.productTypes[0]);

            add('category_id', state.category?.id);
            add('mp_product_type', type?.value);
            add('name', byId('msw-name').value.trim());
            add('sku', byId('msw-sku').value.trim());
            add('url_key', urlKey);

            if (state.variations.length) {
                data.append('variation_data', JSON.stringify(variationPayload()));
                filledOptions(state.variations[0]).forEach((option, position) => {
                    if (option.file) {
                        data.append('variation_images[' + position + ']', option.file);
                    }
                });
            } else {
                add('price', byId('msw-price').value.trim());
                add('special_price', byId('msw-special-price').value.trim());
                add('special_price_from', byId('msw-special-from').value);
                add('special_price_to', byId('msw-special-to').value);
                add('cost', byId('msw-cost-cell').hidden ? '' : byId('msw-cost').value.trim());
                add('stock', byId('msw-stock').value.trim());
            }

            add('weight', byId('msw-weight').value.trim());
            add('short_description', short);
            add('description', descriptionFilled() ? byId('msw-description').innerHTML : '');
            add('status', status);

            if (state.asDraft) {
                add('draft', 1);
            }

            if (byId('msw-new').checked) {
                add('new', 1);
            }

            if (byId('msw-featured').checked) {
                add('featured', 1);
            }

            visibleFields().forEach((field) => add(field.code, (state.values[field.code] ?? '').trim()));

            if (state.promotion) {
                data.append('images[files][image_promotion]', state.promotion.file);
            }

            if (entry.product) {
                data.append('_method', 'PUT');
            }

            state.images.forEach((image, index) => data.append('images[files][' + (image.id ?? 'image_' + index) + ']', image.file ?? ''));

            return data;
        }

        function variationTarget(key) {
            const name = key.match(/^variations\.(\d+)\.name$/);
            const option = key.match(/^variations\.(\d+)\.options\.(\d+)\.label$/);
            const special = key.match(/^(variation_rows\.\d+\.special_price)_(from|to)$/);

            if (special) {
                return special[1];
            }

            if (name) {
                return 'name-' + state.variations[name[1]]?.uid;
            }

            if (option) {
                return 'option-' + (state.variations[option[1]] ? filledOptions(state.variations[option[1]])[option[2]]?.uid : '');
            }

            return key;
        }

        function markErrors(errors) {
            const targets = { name: 'msw-name', sku: 'msw-sku', price: 'msw-price', cost: 'msw-cost', stock: 'msw-stock', weight: 'msw-weight', description: 'msw-description', short_description: 'msw-short', category_id: 'msw-category' };
            let first = null;

            if (errors.short_description && byId('msw-short-row').hidden) {
                byId('msw-short-toggle').click();
            }

            Object.keys(errors).forEach((key) => {
                const target = targets[key] ? byId(targets[key]) : document.querySelector('[data-field="' + key + '"], [data-msw-key="' + variationTarget(key) + '"]');

                if (target) {
                    (target.closest('.msw-add-input, .msw-var-fields') ?? target).classList.add('msw-add-invalid');
                    first = first ?? target;
                }
            });

            first?.scrollIntoView({ block: 'center' });
        }

        function post(status, urlKey) {
            return fetch(entry.urls.store, {
                method: 'POST',
                body: buildPayload(status, urlKey),
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': entry.csrf },
                credentials: 'same-origin',
            });
        }

        async function submit(status) {
            if (state.busy) {
                return;
            }

            state.busy = true;
            refresh();
            clearErrors();

            const baseKey = entry.product?.url_key || slugify(byId('msw-name').value) || slugify(byId('msw-sku').value);

            try {
                let response = await post(status, baseKey);
                let body = await response.json().catch(() => ({}));

                if (response.status === 422 && body.errors?.url_key) {
                    response = await post(status, slugify(baseKey + '-' + byId('msw-sku').value));
                    body = await response.json().catch(() => ({}));
                }

                if (response.ok) {
                    window.location.assign(entry.urls.done[state.asDraft ? 'draft' : status]);

                    return;
                }

                const errors = body.errors ?? {};

                showMessage(Object.values(errors).flat()[0] ?? body.message ?? entry.text.failed);
                markErrors(errors);
            } catch (error) {
                showMessage(entry.text.failed);
            }

            state.busy = false;
            refresh();
        }

        const imagePicker = picker(true, addImages);
        const optionImagePicker = picker(false, (files) => {
            clearErrors();

            if (files[0] && validImage(files[0]) && state.imageTarget) {
                state.imageTarget.file = files[0];
                state.imageTarget.image = URL.createObjectURL(files[0]);
                renderVariationTable();
            }
        });
        const promotionPicker = picker(false, (files) => {
            clearErrors();

            if (files[0] && validImage(files[0])) {
                state.promotion = { file: files[0], url: URL.createObjectURL(files[0]) };
            }

            renderImages();
            refresh();
        });

        byId('msw-images-add').addEventListener('click', () => imagePicker.click());
        byId('msw-promotion-add').addEventListener('click', () => promotionPicker.click());

        byId('msw-images').addEventListener('dragover', (event) => event.preventDefault());
        byId('msw-images').addEventListener('drop', (event) => {
            event.preventDefault();
            addImages(Array.from(event.dataTransfer.files));
        });

        byId('msw-category').addEventListener('focus', (event) => {
            event.target.select();
        });
        byId('msw-category').closest('.msw-add-input').addEventListener('click', () => {
            byId('msw-category').focus();
            renderCategoryList('');
            setCategoryList(true);
        });
        byId('msw-category').addEventListener('input', (event) => {
            renderCategoryList(event.target.value);
            setCategoryList(true);
        });
        document.addEventListener('click', (event) => {
            if (! event.target.closest('.msw-add-combo')) {
                setCategoryList(false);

                if (state.category) {
                    byId('msw-category').value = state.category.path;
                }
            }
        });

        function showFullForm() {
            state.step = 2;
            document.querySelectorAll('[data-msw-step1]').forEach((node) => {
                node.hidden = true;
            });
            document.querySelectorAll('[data-msw-step2]').forEach((node) => {
                node.hidden = false;
            });
            document.querySelector('[data-msw-add]').classList.add('is-full');
            window.scrollTo(0, 0);
        }

        function safeHtml(html) {
            const parsed = new DOMParser().parseFromString(html, 'text/html');

            parsed.querySelectorAll('script, style, iframe, object, embed, link, meta, form').forEach((node) => node.remove());
            parsed.body.querySelectorAll('*').forEach((node) => {
                [...node.attributes].forEach((attribute) => {
                    if (/^on/i.test(attribute.name) || /^\s*javascript:/i.test(attribute.value)) {
                        node.removeAttribute(attribute.name);
                    }
                });
            });

            return parsed.body.innerHTML;
        }

        function loadProduct(product) {
            const category = subcategories.find((item) => item.id === product.category_id);

            state.skuTouched = true;
            state.values = { ...product.values };
            state.images = product.images.map((image) => ({ ...image }));

            ['name', 'sku'].forEach((name) => {
                byId('msw-' + name).value = product[name];
            });
            byId('msw-price').value = product.price;
            byId('msw-cost').value = product.cost;
            byId('msw-stock').value = product.stock;
            byId('msw-weight').value = product.weight;
            byId('msw-short').value = product.short_description;
            byId('msw-description').innerHTML = safeHtml(product.description);
            byId('msw-status').checked = product.status;
            byId('msw-new').checked = product.new;
            byId('msw-featured').checked = product.featured;
            byId('msw-special-price').value = product.special_price;
            byId('msw-special-from').value = product.special_price_from;
            byId('msw-special-to').value = product.special_price_to;

            if (product.special_price) {
                byId('msw-special-open').textContent = entry.currency + ' ' + product.special_price;
            }

            if (product.variation_data) {
                loadVariations(product.variation_data);
            }

            if (category) {
                chooseCategory(category);
            }

            renderImages();
            showFullForm();
        }

        byId('msw-next').addEventListener('click', showFullForm);

        document.querySelectorAll('[data-msw-money]').forEach(bindMoney);

        byId('msw-var-enable').addEventListener('click', () => {
            state.variations = [newVariation()];
            renderVariations();
            document.querySelector('#msw-variations input')?.focus();
        });
        ['price', 'stock', 'sku'].forEach((field) => {
            const input = byId('msw-var-all-' + field);

            input.addEventListener('input', refreshApply);
            input.addEventListener('focus', () => highlight('[data-column-cell="' + field + '"]'));
            input.addEventListener('blur', () => highlight(null));
        });
        byId('msw-var-apply').addEventListener('click', () => {
            ['price', 'stock', 'sku'].forEach((field) => {
                const value = byId('msw-var-all-' + field).value.trim();

                if (value === '') {
                    return;
                }

                byId('msw-var-table').querySelectorAll('[data-row-field="' + field + '"]').forEach((input) => {
                    input.value = value;
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                    input.value = value;
                });

                Object.values(state.rows).forEach((row) => {
                    row[field] = value;
                });
            });
        });

        byId('msw-var-special-form').addEventListener('submit', (event) => {
            const form = event.currentTarget;
            const row = state.specialRow;
            const price = form.elements.price.value.trim();
            const special = form.elements.special_price.value.trim();

            if (special !== '' && Number(special) >= Number(price)) {
                event.preventDefault();
                form.querySelector('[data-msw-message]').textContent = entry.text.variation.specialLower;

                return;
            }

            row.price = price;
            row.special_price = special;
            row.special_price_from = special ? form.elements.special_price_from.value : '';
            row.special_price_to = special ? form.elements.special_price_to.value : '';
            clearErrors();
            renderVariationTable();
        });
        byId('msw-var-special-form').addEventListener('input', (event) => {
            event.currentTarget.querySelector('[data-msw-message]').textContent = '';
        });
        byId('msw-special-open').addEventListener('click', () => byId('msw-special').showModal());
        byId('msw-special').querySelectorAll('[data-msw-add-close]').forEach((button) => button.addEventListener('click', () => byId('msw-special').close()));
        byId('msw-special-ok').addEventListener('click', () => {
            const value = byId('msw-special-price').value.trim();

            byId('msw-special-open').textContent = value ? entry.currency + ' ' + value : entry.text.specialAdd;
            byId('msw-special').close();
        });

        byId('msw-short-toggle').addEventListener('click', (event) => {
            const row = byId('msw-short-row');

            row.hidden = ! row.hidden;
            event.currentTarget.setAttribute('aria-expanded', String(! row.hidden));
            event.currentTarget.firstChild.textContent = (row.hidden ? entry.text.showMore : entry.text.showLess) + ' ';
        });

        document.querySelectorAll('[data-msw-format]').forEach((button) => button.addEventListener('click', () => {
            byId('msw-description').focus();
            document.execCommand(button.dataset.mswFormat);
            refresh();
        }));
        const descriptionPicker = picker(true, uploadDescriptionImages);

        byId('msw-description-image').addEventListener('mousedown', (event) => event.preventDefault());
        byId('msw-description-image').addEventListener('click', () => {
            rememberDescriptionPlace();
            descriptionPicker.click();
        });
        ['keyup', 'mouseup', 'input'].forEach((name) => byId('msw-description').addEventListener(name, rememberDescriptionPlace));
        byId('msw-description').addEventListener('click', (event) => {
            if (event.target.tagName === 'IMG') {
                const range = document.createRange();

                range.selectNode(event.target);
                window.getSelection().removeAllRanges();
                window.getSelection().addRange(range);
                state.descriptionRange = range.cloneRange();
            }
        });
        byId('msw-description').addEventListener('paste', (event) => {
            const files = Array.from(event.clipboardData.files);

            event.preventDefault();

            if (files.length) {
                rememberDescriptionPlace();
                uploadDescriptionImages(files);

                return;
            }

            document.execCommand('insertText', false, event.clipboardData.getData('text/plain'));
        });
        byId('msw-description').addEventListener('dragover', (event) => event.preventDefault());
        byId('msw-description').addEventListener('drop', (event) => {
            const files = Array.from(event.dataTransfer.files);
            const point = document.caretRangeFromPoint ? document.caretRangeFromPoint(event.clientX, event.clientY) : null;

            event.preventDefault();

            if (point && byId('msw-description').contains(point.commonAncestorContainer)) {
                state.descriptionRange = point;
            }

            if (files.length) {
                uploadDescriptionImages(files);

                return;
            }

            byId('msw-description').focus();

            if (point) {
                window.getSelection().removeAllRanges();
                window.getSelection().addRange(point);
            }

            document.execCommand('insertText', false, event.dataTransfer.getData('text/plain'));
        });

        document.querySelectorAll('[data-msw-add-currency]').forEach((node) => {
            node.textContent = entry.currency;
        });

        byId('msw-sku').addEventListener('input', () => {
            state.skuTouched = true;
        });
        ['msw-name', 'msw-sku', 'msw-description'].forEach((id) => byId(id).addEventListener('input', refresh));

        byId('msw-draft').addEventListener('click', () => {
            state.asDraft = true;
            submit(0);
        });
        byId('msw-submit').addEventListener('click', () => {
            state.asDraft = false;
            submit(byId('msw-status').checked ? 1 : 0);
        });

        renderRecent();

        if (entry.product) {
            loadProduct(entry.product);
        }

        refresh();
    })();
</script>
