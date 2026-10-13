import tailwindcss from "@tailwindcss/vite";
import react from "@vitejs/plugin-react";
import { defineConfig } from "vite";

// https://vite.dev/config/
export default defineConfig({
    plugins: [react(), tailwindcss()],
    // The API's CORS and Sanctum settings expect exactly localhost:5173, so fail instead of picking another port.
    server: { port: 5173, strictPort: true },
});
