import { defineConfig } from "vite";
import react from "@vitejs/plugin-react";
import { nodePolyfills } from "vite-plugin-node-polyfills";
import path from "path";

// https://vite.dev/config/
export default defineConfig({
  plugins: [
    react(),
    nodePolyfills({
      // Whether to polyfill `node:` protocol imports.
      protocolImports: true,
    }),
  ],
  optimizeDeps: {
    include: ["@coral-xyz/anchor", "buffer", "process"],
  },
  define: {
    global: "globalThis",
    "process.env": {},
  },
  resolve: {
    alias: {
      crypto: "crypto-browserify",
      stream: "stream-browserify",
      assert: "assert",
      buffer: "buffer",
      process: "process/browser",
      "@coral-xyz/anchor": path.resolve(
        process.cwd(),
        "node_modules/@coral-xyz/anchor"
      ),
    },
  },
  build: {
    rollupOptions: {
      external: [],
      output: {
        globals: {
          buffer: "Buffer",
        },
      },
    },
  },
});
