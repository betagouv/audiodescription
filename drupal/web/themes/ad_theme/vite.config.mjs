import { defineConfig } from 'vite';

export default defineConfig({
  build: {
    outDir: 'dist',
    rollupOptions: {
      input: {
        styles : './src/main.scss',
        scripts: './src/main.js'
      },
      output: {
        entryFileNames: '[name].js', // Regrouper dans le dossier js/
        chunkFileNames: 'js/[name]-[hash].js', // Pour les modules
        assetFileNames: (assetInfo) => {
          // Regrouper les fichiers CSS
          if (assetInfo.name && assetInfo.name.endsWith('.css')) {
            return '[name].[ext]';
          }
          return 'assets/[name].[ext]';
        },
      },
    },
  },
  css: {
    preprocessorOptions: {
      scss: {
      },
    },
  },
});
