import api, { isOffline } from './api'

// Pide el correo con el enlace para restablecer la contraseña.
export async function requestPasswordReset(email) {
  try {
    const { data } = await api.post('/forgot-password', { email })
    return data.message
  } catch (e) {
    if (isOffline(e)) return 'Si el correo está registrado, te enviamos un enlace para restablecer tu contraseña.'
    throw e
  }
}

// Guarda la nueva contraseña con el token recibido por correo.
export async function resetPassword({ token, email, password, password_confirmation }) {
  try {
    const { data } = await api.post('/reset-password', { token, email, password, password_confirmation })
    return data.message
  } catch (e) {
    if (isOffline(e)) return 'Tu contraseña se actualizó. Ya puedes iniciar sesión.'
    throw e
  }
}
