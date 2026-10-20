import imageCompression from 'browser-image-compression'

const DEFAULT_OPTIONS = {
  maxSizeMB: 0.5,
  maxWidthOrHeight: 1920,
  outputFormat: 'webp'
}

/**
 * Composable for client-side image optimization using browser-image-compression.
 * Compresses images to WebP format with configurable size and dimension limits.
 *
 * @returns {{ optimize: (file: File, options?: object) => Promise<object>, optimizeMultiple: (files: File[]) => Promise<object[]> }}
 */
export function useImageOptimizer() {
  /**
   * Optimize a single image file.
   *
   * @param {File} file - The image file to optimize
   * @param {object} [options] - Optional overrides for compression settings
   * @param {number} [options.maxSizeMB=0.5] - Maximum file size in MB (default 500KB)
   * @param {number} [options.maxWidthOrHeight=1920] - Maximum width or height in pixels
   * @param {string} [options.outputFormat='webp'] - Output image format
   * @returns {Promise<{ file: File, originalSize: number, compressedSize: number, url: string }>}
   */
  async function optimize(file, options = {}) {
    const mergedOptions = {
      ...DEFAULT_OPTIONS,
      ...options,
      useWebWorker: true,
      fileType: `image/${options.outputFormat || DEFAULT_OPTIONS.outputFormat}`
    }

    const originalSize = file.size

    const compressedFile = await imageCompression(file, mergedOptions)

    const url = URL.createObjectURL(compressedFile)

    return {
      file: compressedFile,
      originalSize,
      compressedSize: compressedFile.size,
      url
    }
  }

  /**
   * Optimize multiple image files in parallel.
   *
   * @param {File[]} files - Array of image files to optimize
   * @returns {Promise<Array<{ file: File, originalSize: number, compressedSize: number, url: string }>>}
   */
  async function optimizeMultiple(files) {
    return Promise.all(files.map((file) => optimize(file)))
  }

  return {
    optimize,
    optimizeMultiple
  }
}
