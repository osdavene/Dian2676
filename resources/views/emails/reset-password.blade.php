<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - DIAN 2676</title>
</head>
<body style="margin: 0; padding: 0; background-color: #0b1120; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #e2e8f0;">
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #0b1120; padding: 30px 15px;">
        <tr>
            <td align="center">
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; background-color: #1e293b; border-radius: 16px; border: 1px solid #334155; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.5);">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #1e3a8a 0%, #0f172a 100%); padding: 30px 30px 25px; text-align: center; border-bottom: 2px solid #2563eb;">
                            <div style="display: inline-block; padding: 6px 14px; background-color: rgba(37, 99, 235, 0.2); border: 1px solid rgba(59, 130, 246, 0.4); border-radius: 20px; font-size: 11px; font-weight: 700; letter-spacing: 1px; color: #93c5fd; text-transform: uppercase; margin-bottom: 12px;">
                                DIAN • Convocatoria 2676
                            </div>
                            <h1 style="margin: 0; font-size: 22px; font-weight: 800; color: #ffffff; letter-spacing: -0.5px;">
                                Simulador de Pruebas Escritas
                            </h1>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding: 35px 30px;">
                            <h2 style="margin: 0 0 16px; font-size: 18px; font-weight: 700; color: #f8fafc;">
                                Hola, {{ $user->name }} 👋
                            </h2>
                            <p style="margin: 0 0 20px; font-size: 14px; line-height: 1.6; color: #cbd5e1;">
                                Hemos recibido una solicitud para restablecer la contraseña asociada a tu cuenta en el Simulador Oficial del Concurso DIAN 2676.
                            </p>
                            <p style="margin: 0 0 28px; font-size: 14px; line-height: 1.6; color: #cbd5e1;">
                                Para asignar una nueva contraseña segura, haz clic en el siguiente botón:
                            </p>

                            <!-- CTA Button -->
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 30px;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ $resetUrl }}" target="_blank" style="display: inline-block; padding: 14px 32px; background-color: #2563eb; color: #ffffff; font-size: 14px; font-weight: 700; text-decoration: none; border-radius: 12px; box-shadow: 0 4px 14px rgba(37, 99, 235, 0.4); text-transform: uppercase; letter-spacing: 0.5px;">
                                            Restablecer mi Contraseña
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <div style="background-color: #0f172a; border-left: 4px solid #f59e0b; border-radius: 8px; padding: 14px 16px; margin-bottom: 25px;">
                                <p style="margin: 0; font-size: 12px; line-height: 1.5; color: #fcd34d;">
                                    ⏱ <strong>Seguridad:</strong> Este enlace de recuperación expirará automáticamente en <strong>60 minutos</strong>. Si no solicitaste este cambio, puedes ignorar este mensaje; tu cuenta permanece totalmente segura.
                                </p>
                            </div>

                            <p style="margin: 0 0 10px; font-size: 12px; color: #94a3b8;">
                                Si tienes problemas con el botón superior, copia y pega el siguiente enlace directo en tu navegador:
                            </p>
                            <p style="margin: 0; font-size: 11px; line-height: 1.4; word-break: break-all; color: #60a5fa;">
                                <a href="{{ $resetUrl }}" style="color: #60a5fa; text-decoration: underline;">{{ $resetUrl }}</a>
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #0f172a; padding: 20px 30px; text-align: center; border-top: 1px solid #334155;">
                            <p style="margin: 0 0 6px; font-size: 11px; color: #64748b;">
                                Plataforma de Entrenamiento para el Concurso de Méritos DIAN • Convocatoria 2676 de 2024
                            </p>
                            <p style="margin: 0; font-size: 11px; color: #475569;">
                                Este es un mensaje automático. Por favor no responda directamente a este correo.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
