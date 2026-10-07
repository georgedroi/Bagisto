# ManPleasure Catalog Architecture

**Project:** ManPleasure / Bagisto
**Phase:** 3 - Categories & Product Architecture
**Status:** Architecture Defined - Implementation Pending
**Platform:** Bagisto 2.4.12
**Channel:** `default`
**Locale:** `en`
**Currency:** `MYR`

## 1. Architecture Principles

- Use Bagisto native nested categories, EAV attributes, attribute families, simple products, configurable products, channels and inventory sources.
- Do not introduce a custom product type or custom catalog tables for the initial production catalog.
- Keep the catalog discreet, premium, wellness-oriented and non-pornographic.
- Product records must support guest checkout and discreet storefront presentation.
- Reusable merchandising attributes belong in Bagisto attributes, not free-form product descriptions.
- Variant-driving attributes must be finite select attributes and marked configurable.
- Production taxonomy and attributes must remain reusable by the Phase 4 admin editor and Phase 5 media workflow.

## 2. Production Category Hierarchy

The existing Bagisto `Root` category remains the channel root and is not a customer-facing merchandising category.

### 2.1 Strokers & Masturbators
URL key: `strokers-masturbators`

Subcategories:
- Manual Strokers - `manual-strokers`
- Automatic Strokers - `automatic-strokers`
- Sleeves - `sleeves`
- Compact & Travel - `compact-travel`

### 2.2 Rings & Enhancers
URL key: `rings-enhancers`

Subcategories:
- Cock Rings - `cock-rings`
- Vibrating Rings - `vibrating-rings`
- Extension & Enhancement - `extension-enhancement`

### 2.3 Pumps & Trainers
URL key: `pumps-trainers`

Subcategories:
- Manual Pumps - `manual-pumps`
- Electric Pumps - `electric-pumps`
- Training & Support - `training-support`

### 2.4 Prostate & Anal Wellness
URL key: `prostate-anal-wellness`

Subcategories:
- Prostate Massagers - `prostate-massagers`
- Anal Toys - `anal-toys`
- Beginner-Friendly - `beginner-friendly`

### 2.5 Lubricants & Care
URL key: `lubricants-care`

Subcategories:
- Water-Based Lubricants - `water-based-lubricants`
- Silicone-Based Lubricants - `silicone-based-lubricants`
- Toy Cleaners - `toy-cleaners`
- Personal Care - `personal-care`

### 2.6 Accessories & Storage
URL key: `accessories-storage`

Subcategories:
- Storage - `storage`
- Replacement Parts - `replacement-parts`
- Chargers & Cables - `chargers-cables`
- Travel Accessories - `travel-accessories`

## 3. Category Rules

- Category names use title case.
- URL keys use lowercase ASCII kebab-case.
- URL keys are stable identifiers and should not be changed for cosmetic copy edits.
- Every customer-facing category must have an English name, description, meta title and meta description before production launch.
- Main categories should receive a square/landscape category image and a wide banner where the storefront design uses one.
- Subcategories require a category image only when surfaced visually.
- Category sorting is manually controlled through Bagisto position values.
- Products may belong to more than one relevant leaf category, but one category should be treated editorially as the primary merchandising context.
- Avoid duplicate categories based only on brand, colour, size or material; those belong to attributes/filters.

## 4. Attribute Family Strategy

### 4.1 Default
Retain Bagisto's native `default` family for generic/simple products and compatibility.

### 4.2 ManPleasure Core
Create `manpleasure_core` as the production family for most physical adult-wellness products.

Groups:
1. General
2. Product Details
3. Compatibility & Fit
4. Features
5. Price
6. Description
7. Shipping
8. SEO
9. Settings
10. Inventory
11. RMA

### 4.3 ManPleasure Consumable
Create `manpleasure_consumable` for lubricants, cleaners and personal-care consumables.

Groups:
1. General
2. Product Details
3. Ingredients & Care
4. Price
5. Description
6. Shipping
7. SEO
8. Settings
9. Inventory
10. RMA

## 5. Reusable Product Attributes

Keep Bagisto native attributes such as `sku`, `name`, `url_key`, `price`, `weight`, `brand`, `status`, `guest_checkout`, `manage_stock`, SEO fields and descriptions.

Create the following reusable attributes where not already present:

| Code | Admin Name | Type | Filterable | Configurable | Visible on Front | Family |
|---|---|---|---:|---:|---:|---|
| `mp_material` | Material | select | Yes | No | Yes | Core |
| `mp_product_type` | Product Type | select | Yes | No | Yes | Core |
| `mp_power_source` | Power Source | select | Yes | No | Yes | Core |
| `mp_water_resistance` | Water Resistance | select | Yes | No | Yes | Core |
| `mp_noise_level` | Noise Level | select | Yes | No | Yes | Core |
| `mp_firmness` | Firmness | select | Yes | Yes | Yes | Core |
| `mp_fit_size` | Fit Size | select | Yes | Yes | Yes | Core |
| `mp_variant_color` | Variant Colour | select | Yes | Yes | Yes | Core |
| `mp_length_cm` | Length (cm) | text | No | No | Yes | Core |
| `mp_diameter_cm` | Diameter (cm) | text | No | No | Yes | Core |
| `mp_rechargeable` | Rechargeable | boolean | Yes | No | Yes | Core |
| `mp_app_controlled` | App Controlled | boolean | Yes | No | Yes | Core |
| `mp_body_safe` | Body-Safe Material | boolean | Yes | No | Yes | Core |
| `mp_care_instructions` | Care Instructions | textarea | No | No | Yes | Core/Consumable |
| `mp_compatibility` | Compatibility | textarea | No | No | Yes | Core |
| `mp_volume_ml` | Volume (ml) | text | No | No | Yes | Consumable |
| `mp_lubricant_base` | Lubricant Base | select | Yes | No | Yes | Consumable |
| `mp_condom_compatible` | Condom Compatible | boolean | Yes | No | Yes | Consumable |
| `mp_toy_compatible` | Toy Compatible | boolean | Yes | No | Yes | Consumable |

Initial finite option sets should be conservative. New options are added only when a real product requires them.

## 6. Existing Generic Attributes

The existing Bagisto `color` and `size` attributes remain available for compatibility.

For ManPleasure production data:
- Prefer `mp_variant_color` when colour is a true sellable variant.
- Prefer `mp_fit_size` when fit/diameter sizing is a true sellable variant.
- Do not use generic apparel-style size options (`S`, `M`, `L`, `XL`) unless they accurately describe the product.
- `brand` remains a reusable filterable front-facing attribute.

## 7. Product Type Convention

### Simple Product
Use a simple product when there is exactly one sellable SKU with one price/inventory identity.

Examples:
- one lubricant bottle size
- one cleaner
- one accessory
- a device with no selectable variant

### Configurable Product
Use a configurable product when the customer selects one or more meaningful options that resolve to distinct sellable child SKUs.

Approved variant dimensions:
- colour
- fit size
- firmness
- volume only when each volume is stocked/priced independently

Do not create configurable products for:
- descriptive features
- material when it does not change the SKU
- packaging copy
- non-stocked cosmetic differences

Each variant must have its own SKU, price where applicable, inventory quantity and variant-specific media when the visible product differs.

## 8. SKU Convention

Format:

`MP-{CATEGORY}-{PRODUCT}-{VARIANT}`

Rules:
- uppercase ASCII
- hyphen separated
- no spaces
- immutable after the SKU is used operationally
- category token: 2-4 characters
- product token: short stable identifier
- variant token: optional for simple products, mandatory for configurable children

Recommended category tokens:
- `STM` - Strokers & Masturbators
- `RNG` - Rings & Enhancers
- `PMP` - Pumps & Trainers
- `PAW` - Prostate & Anal Wellness
- `LBC` - Lubricants & Care
- `ACC` - Accessories & Storage

Examples:
- `MP-LBC-AQUA100`
- `MP-STM-PULSE-BLK`
- `MP-RNG-FLEX-45MM`

The configurable parent may use a parent SKU ending in `-PARENT` when needed by Bagisto administration.

## 9. Product Naming Convention

Customer-facing name:

`{Brand} {Model/Product Name} {Key Differentiator}`

Rules:
- do not place the SKU in the customer-facing name
- avoid keyword stuffing
- avoid explicit/pornographic marketing language
- keep differentiators useful: material, technology, size/volume or generation
- variant colour/fit belongs in the child variant context rather than bloating the parent name

## 10. URL-Key Convention

Format:

`{brand}-{product-name}-{key-differentiator}`

Rules:
- lowercase ASCII
- kebab-case
- no SKU unless needed to resolve a collision
- no price, campaign, stock status or temporary marketing phrase
- configurable variants do not require separate public URL keys when they are not individually visible
- once indexed in production, URL-key changes require redirect handling

## 11. Inventory Assumptions

- Initial production inventory source remains Bagisto `default`.
- `manage_stock` is enabled for physical sellable SKUs unless an explicit exception is documented.
- Inventory is tracked at the simple SKU/variant level.
- Configurable parents do not carry independent sellable stock.
- Stock quantities must never be duplicated across parent and child records.
- Consumables and accessories use the same inventory source until multi-location fulfilment is introduced.
- Phase 3 does not introduce warehouse custom tables.

Before production launch, replace the seed/default inventory-source contact/address metadata with the actual fulfilment location.

## 12. SEO Ownership Boundaries

### Category owns
- category name
- category description
- category meta title
- category meta description
- category URL key
- category image/banner alt text
- category-level internal-linking copy

### Product owns
- product name
- product short description
- product long description
- product meta title
- product meta description
- product URL key
- product image alt text
- product-specific structured data inputs

### Storefront/theme owns
- global organization/site metadata
- global Open Graph defaults
- breadcrumbs presentation
- canonical rendering
- sitemap/robots integration
- shared social defaults

Do not duplicate the same long-form SEO copy across categories and products.

## 13. Media Requirements

### Product
Minimum production target:
- 1 primary image
- 3 supporting images where available
- 1 packaging/discreet-delivery image where useful
- variant-specific image for visually distinct configurable variants

Recommended source:
- square or near-square
- at least 1200 px on the shortest side
- WebP/JPEG/PNG as supported
- clean background
- no pornographic imagery
- no embedded marketplace watermarks

### Category
- main category image: minimum 1200 px wide
- banner where used: minimum 1920 px wide
- meaningful alt text
- consistent premium/discreet visual language

### Video
- optional product demonstration/feature video
- no autoplay with sound
- must remain suitable for the site's discreet wellness positioning

Phase 4/5 media tooling must support reuse, search, folders/categories, metadata, duplicate detection, safe deletion and variant/product assignment.

## 14. Controlled Test Product Strategy

Before real production catalog import:
1. Create one simple test product using `manpleasure_core`.
2. Create one configurable test product with exactly one or two approved super attributes.
3. Verify category assignment.
4. Verify filterable attributes.
5. Verify variant selection, price, gallery and inventory.
6. Verify URL keys and SEO metadata.
7. Verify storefront responsive behavior.
8. Verify cleanup can return the catalog to zero test products.

Test fixtures must use unmistakable `MP-QA-` SKUs and must not be confused with production inventory.

## 15. Phase 3 Implementation Order

1. Create production category hierarchy.
2. Create reusable ManPleasure attributes/options.
3. Create `manpleasure_core` and `manpleasure_consumable` families.
4. Map attributes into family groups.
5. Configure category filterable attributes where appropriate.
6. Replace default inventory-source seed metadata when owner data is available.
7. Create controlled simple/configurable QA products.
8. Run storefront/admin verification.
9. Remove QA products.
10. Finalize Phase 3 tracker gate.

## 16. Phase 3 Decisions

- Native Bagisto catalog structures only.
- No custom product type.
- No custom catalog tables.
- Production hierarchy is product-function based, not brand based.
- Brand, material, power source and other reusable facets are attributes.
- Variants are reserved for choices that produce distinct sellable SKUs.
- Inventory is variant/SKU owned.
- SEO ownership is explicitly separated between category, product and theme.
- Media requirements are defined now so Phase 4/5 admin/media work has a stable contract.
