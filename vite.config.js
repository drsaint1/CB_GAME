import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import nodePolyfills from 'rollup-plugin-polyfill-node';
import process from 'process';
import path from 'path';

// https://vite.dev/config/
export default defineConfig({
  plugins: [
    react(),
    nodePolyfills(), // Add the polyfills plugin
  ],
  optimizeDeps: {
    include: ['@coral-xyz/anchor'], // Explicitly include the Anchor library for optimization
  },
  define: {
    'process.env': {},
  },
  resolve: {
    alias: {
      crypto: 'crypto-browserify',
      stream: 'stream-browserify',
      assert: 'assert',
      buffer: 'buffer',
      '@coral-xyz/anchor': path.resolve(__dirname, 'node_modules/@coral-xyz/anchor'),
    },
  },
});