import { expect } from "@playwright/test";
import { test } from "../../setup";
import { ProductGalleryPage } from "../../pages/shop/catalog/ProductGalleryPage";

test.describe("product gallery", () => {
    test("should recover mobile gallery image to placeholder when media fails", async ({
        shopPage,
    }) => {
        const urlKey = process.env.GALLERY_REGRESSION_URL_KEY;
        const productName = process.env.GALLERY_REGRESSION_NAME;
        const expectedPlaceholder = process.env.GALLERY_REGRESSION_PLACEHOLDER;
        const brokenFile = process.env.GALLERY_REGRESSION_BROKEN_FILE;

        expect(urlKey).toBeTruthy();
        expect(productName).toBeTruthy();
        expect(expectedPlaceholder).toBeTruthy();
        expect(brokenFile).toBeTruthy();

        const pageErrors: string[] = [];

        shopPage.on("pageerror", (error) => {
            pageErrors.push(error.message);
        });

        const gallery = new ProductGalleryPage(shopPage);

        await gallery.openProduct(urlKey as string, productName as string);
        await gallery.expectBrokenImageToRecover(
            productName as string,
            expectedPlaceholder as string,
            brokenFile as string,
        );

        expect(pageErrors).toEqual([]);
    });
});
