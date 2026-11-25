/**
 * Convierte un enlace normal de YouTube, Vimeo o Google Drive en la URL
 * de "embed" que se puede usar dentro de un <iframe>. Si no reconoce el
 * formato, regresa el enlace tal cual (por si ya es una URL de embed).
 */
export function getEmbedUrl(url) {
  if (!url) return null

  try {
    const u = new URL(url)

    // YouTube: youtube.com/watch?v=ID  |  youtube.com/embed/ID  |  youtu.be/ID
    if (u.hostname.includes('youtube.com')) {
      const id = u.searchParams.get('v')
      if (id) return `https://www.youtube.com/embed/${id}`
      if (u.pathname.startsWith('/embed/')) return url
    }
    if (u.hostname === 'youtu.be') {
      const id = u.pathname.slice(1)
      return `https://www.youtube.com/embed/${id}`
    }

    // Vimeo: vimeo.com/123456789
    if (u.hostname.includes('vimeo.com')) {
      const match = u.pathname.match(/(\d+)/)
      if (match) return `https://player.vimeo.com/video/${match[1]}`
    }

    // Google Drive: drive.google.com/file/d/FILE_ID/view
    if (u.hostname.includes('drive.google.com')) {
      const match = u.pathname.match(/\/d\/([^/]+)/)
      if (match) return `https://drive.google.com/file/d/${match[1]}/preview`
    }

    // Ya es un enlace de embed, o un video directo (.mp4, etc.)
    return url
  } catch (e) {
    return url
  }
}

/** true si la URL parece un archivo de video directo (mp4, webm, ogg). */
export function isDirectVideoFile(url) {
  return !!url && /\.(mp4|webm|ogg)(\?.*)?$/i.test(url)
}
