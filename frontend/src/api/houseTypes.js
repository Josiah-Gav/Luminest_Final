import client from "./client";

// M2 House Catalog (plan §5.2). Public, no login needed.

export const getHouseTypes = async () =>
    (await client.get("/api/house-types")).data.data;

// Includes available_units: [{ id, block, lot }]
export const getHouseType = async (slug) =>
    (await client.get(`/api/house-types/${slug}`)).data.data;

// image_path ("aimee.jpg") → the photo bundled in src/assets/houses/.
export const houseImageUrl = (houseType) =>
    new URL(`../assets/houses/${houseType.image_path}`, import.meta.url).href;
