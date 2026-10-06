import { expect, Page } from "@playwright/test";
import { BasePage } from "../../BasePage";

export class ProductGalleryPage extends BasePage {
    constructor(page: Page) {
        super(page);
    }

    private mobileGalleryImage(productName: string) {
        return this.page
            .locator("div.scrollbar-hide.w-screen.overflow-auto")
            .locator(`img[alt="${productName}"]`)
            .first();
    }

    async openProduct(urlKey: string, productName: string) {
        await this.page.setViewportSize({
            width: 390,
            height: 844,
        });

        const normalized = urlKey.replace(/^\/+/, "");

        await this.page.goto(normalized, {
            waitUntil: "domcontentloaded",
        });

        await expect(
            this.page.getByRole("heading", {
                name: productName,
                exact: true,
            }).first(),
        ).toBeVisible();
    }

    async expectBrokenImageToRecover(
        productName: string,
        expectedPlaceholder: string,
        brokenFile: string,
    ) {
        const image = this.mobileGalleryImage(productName);

        await expect(image).toBeVisible();

        await expect(
            image.locator("xpath=ancestor::div[contains(@class,'scrollbar-hide') and contains(@class,'w-screen') and contains(@class,'overflow-auto')]"),
        ).toHaveCount(1);

        await expect.poll(async () => {
            const src = await image.getAttribute("src");

            if (! src) {
                return "";
            }

            return new URL(src, this.page.url()).href;
        }).toBe(new URL(expectedPlaceholder, this.page.url()).href);

        const src = await image.getAttribute("src");

        expect(src).not.toContain(brokenFile);
    }
}
