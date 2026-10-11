<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido al Simulador DIAN 2676</title>
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
                                ¡Tu cuenta ha sido creada exitosamente!
                            </h1>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding: 35px 30px;">
                            <h2 style="margin: 0 0 12px; font-size: 18px; font-weight: 700; color: #f8fafc;">
                                ¡Bienvenido a bordo, {{ $user->name }}! 🎉
                            </h2>
                            <p style="margin: 0 0 20px; font-size: 14px; line-height: 1.6; color: #cbd5e1;">
                                Te has registrado exitosamente en la plataforma de entrenamiento para el concurso de méritos de la DIAN.
                            </p>

                            <!-- Cargo badge -->
                            <div style="background-color: #0f172a; border-radius: 12px; padding: 18px 20px; margin-bottom: 25px; border: 1px solid #334155;">
                                <p style="margin: 0 0 6px; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; font-weight: 700;">
                                    Cargo Meta Asignado:
                                </p>
                                <p style="margin: 0; font-size: 16px; font-weight: 800; color: #60a5fa;">
                                    {{ $user->target_cargo ?? 'Aspirante DIAN' }}
                                </p>
                            </div>

                            <p style="margin: 0 0 16px; font-size: 14px; font-weight: 600; color: #f8fafc;">
                                Todo lo que tienes listo para tu preparación:
                            </p>
                            <ul style="margin: 0 0 28px; padding-left: 20px; font-size: 13px; line-height: 1.8; color: #cbd5e1;">
                                <li><strong>Simulacro Oficial (100 preguntas):</strong> Cronómetro pausado de 5 horas adaptado al ritmo real del examen.</li>
                                <li><strong>Simulacro Express (50 preguntas):</strong> Para entrenar cuando dispongas de sesiones cortas.</li>
                                <li><strong>Historial y Estadísticas:</strong> Diagnóstico detallado por ejes temáticos para identificar fortalezas y debilidades.</li>
                                <li><strong>Repaso de Fallos:</strong> Módulo exclusivo para retroalimentar las preguntas que respondiste incorrectamente.</li>
                            </ul>

                            <!-- CTA Button -->
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 20px;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ url('/login') }}" target="_blank" style="display: inline-block; padding: 14px 32px; background-color: #2563eb; color: #ffffff; font-size: 14px; font-weight: 700; text-decoration: none; border-radius: 12px; box-shadow: 0 4px 14px rgba(37, 99, 235, 0.4); text-transform: uppercase; letter-spacing: 0.5px;">
                                            Iniciar mi Primer Simulacro
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #0f172a; padding: 20px 30px; text-align: center; border-top: 1px solid #334155;">
                            <p style="margin: 0 0 6px; font-size: 11px; color: #64748b;">
                                Plataforma de Entrenamiento para el Concurso de Méritos DIAN • Convocatoria 2676 de 2024
                            </p>
                            <p style="margin: 0; font-size: 11px; color: #475569;">
                                Has recibido este correo porque te registraste en nuestra plataforma.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
