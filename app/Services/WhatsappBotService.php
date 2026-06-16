<?php

namespace App\Services;

use App\Http\Controllers\ReniecController;
use App\Models\Appointment;
use App\Models\Configuration;
use App\Models\Conversation;
use App\Models\DoctorSchedule;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Promotion;
use App\Models\Raffle;
use App\Models\RaffleParticipant;
use App\Models\Rating;
use App\Models\TreatmentContract;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsappBotService
{
    protected $evolutionApi;

    protected $assistantName;
    protected $clinicName;

    // Estados del bot
    const STATE_WELCOME = 'welcome';

    const STATE_ASK_DNI = 'ask_dni';

    const STATE_ASK_ADDRESS = 'ask_address';

    const STATE_MAIN_MENU = 'main_menu';

    const STATE_ASK_APPOINTMENT_DATE = 'ask_appointment_date';

    const STATE_ASK_APPOINTMENT_TIME = 'ask_appointment_time';

    const STATE_ASK_APPOINTMENT_REASON = 'ask_appointment_reason';

    const STATE_ASK_RATING = 'ask_rating';

    const STATE_ASK_RATING_COMMENT = 'ask_rating_comment';

    const STATE_RESCHEDULE_SELECT = 'reschedule_select';

    const STATE_RESCHEDULE_DATE = 'reschedule_date';

    const STATE_RESCHEDULE_TIME = 'reschedule_time';

    const STATE_CONFIRM_APPOINTMENT = 'confirm_appointment';

    const STATE_RAFFLE_JOIN = 'raffle_join';

    public function __construct(EvolutionApiService $evolutionApi)
    {
        $this->evolutionApi = $evolutionApi;
        $this->assistantName = Configuration::get('whatsapp_bot_name') ?: 'Asistente Virtual';
        $this->clinicName = Configuration::get('clinica_nombre') ?: 'la clínica';
    }

    public function processMessage(Conversation $conversation, string $messageText)
    {
        // Solo respondemos si el bot está activo
        if ($conversation->bot_status !== 'active') {
            return;
        }

        $messageText = trim(strtolower($messageText));
        $state = $conversation->bot_state;

        // Comandos globales - el paciente puede salir o ir al menú en cualquier momento
        $globalCommands = ['salir', 'menu', 'menú', 'inicio', 'cancelar', 'cancel'];
        if (in_array($messageText, $globalCommands)) {
            if ($conversation->patient_id) {
                $this->sendTypingIndicator($conversation->phone_number);
                $this->sendMessage($conversation->phone_number, "👋 *¡Volviendo al menú principal!*\n\n¿En qué más puedo ayudarte?");
                sleep(1);
                $this->setMenuState($conversation, true);
            } else {
                $this->setWelcomeState($conversation);
            }

            return;
        }

        // Interceptar saludos para enviar el menú principal en lugar de error
        $greetings = ['hola', 'buenas', 'buenos dias', 'buenos días', 'buenas tardes', 'buenas noches', 'saludos', 'hi', 'hello'];
        if (in_array($messageText, $greetings)) {
            if ($conversation->patient_id) {
                $this->sendTypingIndicator($conversation->phone_number);
                $this->setMenuState($conversation, true);
            } else {
                $this->setWelcomeState($conversation);
            }

            return;
        }

        // Procesar según el estado actual
        switch ($state) {
            case self::STATE_WELCOME:
                $this->handleWelcome($conversation, $messageText);
                break;
            case self::STATE_ASK_DNI:
                $this->handleAskDni($conversation, $messageText);
                break;
            case self::STATE_ASK_ADDRESS:
                $this->handleAskAddress($conversation, $messageText);
                break;
            case self::STATE_MAIN_MENU:
                $this->handleMainMenu($conversation, $messageText);
                break;
            case self::STATE_ASK_APPOINTMENT_DATE:
                $this->handleAskAppointmentDate($conversation, $messageText);
                break;
            case self::STATE_ASK_APPOINTMENT_TIME:
                $this->handleAskAppointmentTime($conversation, $messageText);
                break;
            case self::STATE_ASK_APPOINTMENT_REASON:
                $this->handleAskAppointmentReason($conversation, $messageText);
                break;
            case self::STATE_ASK_RATING:
                $this->handleAskRating($conversation, $messageText);
                break;
            case self::STATE_ASK_RATING_COMMENT:
                $this->handleAskRatingComment($conversation, $messageText);
                break;
            case self::STATE_RESCHEDULE_SELECT:
                $this->handleRescheduleSelect($conversation, $messageText);
                break;
            case self::STATE_RESCHEDULE_DATE:
                $this->handleRescheduleDate($conversation, $messageText);
                break;
            case self::STATE_RESCHEDULE_TIME:
                $this->handleRescheduleTime($conversation, $messageText);
                break;
            case self::STATE_CONFIRM_APPOINTMENT:
                $this->handleConfirmAppointment($conversation, $messageText);
                break;
            case self::STATE_RAFFLE_JOIN:
                $this->handleRaffleJoin($conversation, $messageText);
                break;
            default:
                if ($conversation->patient_id) {
                    $this->setMenuState($conversation, true);
                } else {
                    $this->setWelcomeState($conversation);
                }
                break;
        }
    }

    /**
     * ============================================
     * FLUJO DE BIENVENIDA Y REGISTRO
     * ============================================
     */
    protected function handleWelcome(Conversation $conversation, string $messageText)
    {
        // Verificar si ya existe el paciente por teléfono
        if (! $conversation->patient_id) {
            $existingPatient = Patient::where('phone', $conversation->phone_number)->first();
            if ($existingPatient) {
                $conversation->update(['patient_id' => $existingPatient->id]);
                $this->sendWelcomeBackMessage($conversation, $existingPatient);
                $this->checkPendingNotifications($conversation, $existingPatient);

                return;
            }
        }

        if ($conversation->patient_id) {
            $patient = Patient::find($conversation->patient_id);
            $this->sendWelcomeBackMessage($conversation, $patient);
            $this->checkPendingNotifications($conversation, $patient);

            return;
        }

        // Nuevo paciente - flujo de registro
        $reply = "🦷✨ *¡Hola! Bienvenido(a) a {$this->clinicName}* ✨🦷\n\n";
        $reply .= "Soy *{$this->assistantName}*, tu asistente virtual. Estoy aquí para ayudarte con tus citas, consultas y todo lo que necesites para mantener tu sonrisa radiante. 😊\n\n";
        $reply .= "Para comenzar con tu registro y brindarte una atención personalizada, ¿podrías indicarme tu número de *DNI*? (8 dígitos)\n\n";
        $reply .= '📝 _Ejemplo: 12345678_';

        $conversation->update(['bot_state' => self::STATE_ASK_DNI]);
        $this->sendTypingIndicator($conversation->phone_number);
        sleep(1);
        $this->sendMessage($conversation->phone_number, $reply);
    }

    protected function sendWelcomeBackMessage(Conversation $conversation, $patient)
    {
        $greeting = $this->getGreeting();
        $reply = "{$greeting} *{$patient->first_name}*! 👋😊\n\n";
        $reply .= "Soy *{$this->assistantName}*, tu asistente dental de confianza. 🦷✨\n";
        $reply .= "¡Qué gusto verte de nuevo por aquí!\n\n";
        $reply .= '¿En qué puedo ayudarte hoy?';

        $this->sendMessage($conversation->phone_number, $reply);
        sleep(1);
        $this->setMenuState($conversation, true);
    }

    protected function getGreeting()
    {
        $hour = now()->hour;
        if ($hour < 12) {
            return '¡Buenos días';
        }
        if ($hour < 18) {
            return '¡Buenas tardes';
        }

        return '¡Buenas noches';
    }

    protected function handleAskDni(Conversation $conversation, string $messageText)
    {
        // Validar DNI: 8 dígitos numéricos
        if (! preg_match('/^\d{8}$/', $messageText)) {
            $reply = "🤔 *Ups, ese DNI no parece válido.*\n\n";
            $reply .= "El DNI peruano debe tener exactamente *8 dígitos numéricos*.\n";
            $reply .= "Por favor, inténtalo de nuevo. 📝\n\n";
            $reply .= '📌 _Ejemplo: 12345678_';

            $this->sendMessage($conversation->phone_number, $reply);

            return;
        }

        $dni = $messageText;

        // Consultar RENIEC
        try {
            $this->sendTypingIndicator($conversation->phone_number);
            $reniec = new ReniecController;
            $request = new Request;
            $response = $reniec->searchDni($request, $dni);

            $data = json_decode($response->getContent(), true);

            if (isset($data['success']) && $data['success']) {
                $nombreCompleto = $data['data']['nombre_completo'] ?? '';
                $nombres = $data['data']['nombres'] ?? '';
                $apellidos = trim(($data['data']['apellido_paterno'] ?? '').' '.($data['data']['apellido_materno'] ?? ''));

                $reply = "✅ *¡Perfecto! Encontré tus datos:*\n\n";
                $reply .= "👤 *Nombre:* {$nombreCompleto}\n\n";
                $reply .= "📋 Ahora, para completar tu registro, necesito un dato más:\n";
                $reply .= "¿Podrías indicarme tu *dirección actual* o lugar de residencia? 🏠\n\n";
                $reply .= '📝 _Ejemplo: Av. Los Olivos 123, San Isidro_';

                $botData = $conversation->bot_data ?? [];
                $botData['register'] = [
                    'dni' => $dni,
                    'first_name' => $nombres,
                    'last_name' => $apellidos,
                ];

                $conversation->update([
                    'bot_state' => self::STATE_ASK_ADDRESS,
                    'bot_data' => $botData,
                ]);

                $this->sendMessage($conversation->phone_number, $reply);
            } else {
                $reply = "😔 *Lo siento, no encontré tus datos en el registro nacional.*\n\n";
                $reply .= "Por favor, verifica que el DNI sea correcto e inténtalo de nuevo.\n";
                $reply .= "Si el problema persiste, puedes escribir *'hablar con asesor'* para recibir ayuda personalizada. 👩‍💼";

                $this->sendMessage($conversation->phone_number, $reply);
            }
        } catch (\Exception $e) {
            Log::error('Error en bot RENIEC: '.$e->getMessage());
            $reply = "⚠️ *Tuvimos un problema técnico al verificar tu DNI.*\n\n";
            $reply .= "No te preocupes, puedes intentarlo nuevamente en unos minutos o escribir *'hablar con asesor'* para que te ayudemos personalmente. 👩‍💼";

            $this->sendMessage($conversation->phone_number, $reply);
        }
    }

    protected function handleAskAddress(Conversation $conversation, string $messageText)
    {
        if (strlen($messageText) < 5) {
            $reply = "🤔 *La dirección que ingresaste es muy corta.*\n\n";
            $reply .= "Por favor, escríbeme una dirección más detallada para ubicarte mejor. 🏠\n";
            $reply .= '📝 _Ejemplo: Av. Los Olivos 123, San Isidro_';

            $this->sendMessage($conversation->phone_number, $reply);

            return;
        }

        $address = $messageText;
        $botData = $conversation->bot_data;

        if (! isset($botData['register'])) {
            $this->setWelcomeState($conversation);

            return;
        }

        // Crear o actualizar el paciente
        $patient = Patient::updateOrCreate(
            ['dni' => $botData['register']['dni']],
            [
                'first_name' => $botData['register']['first_name'],
                'last_name' => $botData['register']['last_name'],
                'phone' => $conversation->phone_number,
                'address' => $address,
                'registration_date' => now(),
            ]
        );

        $conversation->update([
            'patient_id' => $patient->id,
            'bot_data' => null,
        ]);

        $reply = "🎉✨ *¡Registro exitoso!* ✨🎉\n\n";
        $reply .= "Bienvenido(a) a la familia de *{$this->clinicName}*, {$patient->first_name}. 🦷💙\n\n";
        $reply .= 'Estamos encantados de tenerte con nosotros. A continuación te muestro todo lo que puedo hacer por ti:';

        $this->sendMessage($conversation->phone_number, $reply);
        sleep(2);

        // Enviar mensaje de bienvenida con información útil
        $clinicaTelefono = Configuration::get('clinica_telefono');
        $clinicaDireccion = Configuration::get('clinica_direccion');
        $clinicaHorario = Configuration::get('clinica_horario');

        $info = "📌 *INFORMACIÓN IMPORTANTE:*\n\n";
        if ($clinicaHorario) {
            $info .= "🕐 *Horario:* {$clinicaHorario}\n";
        }
        if ($clinicaTelefono) {
            $info .= "📞 *Emergencias:* {$clinicaTelefono}\n";
        }
        if ($clinicaDireccion) {
            $info .= "🏥 *Dirección:* {$clinicaDireccion}\n\n";
        }
        $info .= "Recuerda que también te enviaré:\n";
        $info .= "✅ Recordatorios de tus citas\n";
        $info .= "🎂 Saludos de cumpleaños\n";
        $info .= "🦷 Promociones especiales\n";
        $info .= "⭐ Oportunidades para valorar nuestro servicio\n\n";
        $info .= '¡Ahora sí, veamos el menú! 👇';

        $this->sendMessage($conversation->phone_number, $info);
        sleep(2);

        $this->setMenuState($conversation, true);
    }

    /**
     * ============================================
     * MENÚ PRINCIPAL
     * ============================================
     */
    protected function setWelcomeState(Conversation $conversation)
    {
        $conversation->update(['bot_state' => self::STATE_WELCOME]);
        $this->handleWelcome($conversation, '');
    }

    public function setMenuState(Conversation $conversation, bool $sendMessage = false)
    {
        $conversation->update(['bot_state' => self::STATE_MAIN_MENU]);

        if ($sendMessage) {
            $patient = Patient::find($conversation->patient_id);
            $firstName = $patient ? explode(' ', $patient->first_name)[0] : '';

            $menu = "🦷✨ *MENÚ PRINCIPAL* ✨🦷\n\n";
            if ($firstName) {
                $menu .= "¡Hola, *{$firstName}*! ¿En qué puedo ayudarte?\n\n";
            }
            $menu .= "Escribe el *número* de la opción que deseas:\n\n";
            $menu .= "1️⃣ 📅 *Agendar nueva cita*\n";
            $menu .= "2️⃣ 📋 *Ver mis próximas citas*\n";
            $menu .= "3️⃣ 💰 *Consultar mis deudas*\n";
            $menu .= "4️⃣ 🔄 *Reprogramar una cita*\n";
            $menu .= "5️⃣ 📝 *Confirmar cita pendiente*\n";
            $menu .= "6️⃣ 🦷 *Campañas y promociones*\n";
            $menu .= "7️⃣ 🎁 *Sorteos vigentes*\n";
            $menu .= "8️⃣ 🧾 *Historial de pagos*\n";
            $menu .= "9️⃣ 👩‍💼 *Hablar con un asesor*\n";
            $menu .= "0️⃣ ⭐ *Valorar nuestro servicio*\n\n";
            $menu .= "💡 _También puedes escribir *'salir'* o *'cancelar'* en cualquier momento para volver aquí._";

            $this->sendMessage($conversation->phone_number, $menu);
        }
    }

    protected function handleMainMenu(Conversation $conversation, string $messageText)
    {
        $messageText = trim($messageText);

        if (preg_match('/^ticket\s+(\d+)$/i', $messageText, $matches)) {
            $this->sendPaymentPdf($conversation, $matches[1]);

            return;
        }

        switch ($messageText) {
            case '1':
                $this->startAppointmentFlow($conversation);
                break;
            case '2':
                $this->showAppointments($conversation);
                break;
            case '3':
                $this->showDebts($conversation);
                break;
            case '4':
                $this->showRescheduleOptions($conversation);
                break;
            case '5':
                $this->showPendingConfirmations($conversation);
                break;
            case '6':
                $this->showPromotions($conversation);
                break;
            case '7':
                $this->showRaffles($conversation);
                break;
            case '8':
                $this->showPaymentHistory($conversation);
                break;
            case '9':
                $this->transferToHuman($conversation);
                break;
            case '0':
            case 0:
                $this->startRatingFlow($conversation);
                break;
            default:
                $normalizedText = strtolower($messageText);

                // Si el usuario responde "sí" al recordatorio
                if (in_array($normalizedText, ['si', 'sí', 'confirmar', 'confirmo', 'confirmado'])) {
                    $appointment = Appointment::where('patient_id', $conversation->patient_id)
                        ->where('date', '>=', now()->format('Y-m-d'))
                        ->where('status', 'pending')
                        ->orderBy('date', 'asc')
                        ->first();

                    if ($appointment) {
                        $appointment->update(['status' => 'confirmed']);
                        $reply = "✅✨ *¡Cita confirmada exitosamente!*\n\n";
                        $reply .= "Nos alegra saber que asistirás. Te esperamos puntualmente.\n";
                        $reply .= '¡Que tengas un excelente día! 🦷💙';
                        $this->sendMessage($conversation->phone_number, $reply);
                        sleep(2);
                        $this->setMenuState($conversation);

                        return;
                    }
                }

                // Si responde "reprogramar"
                if (in_array($normalizedText, ['reprogramar', 'reprograma', 'cambiar cita'])) {
                    $this->showRescheduleOptions($conversation);

                    return;
                }

                $reply = "🤔 *Opción no válida.*\n\n";
                $reply .= "Por favor, escribe solo el *número* de la opción que deseas (0-9).\n";
                $reply .= '¡Así podré ayudarte mejor! 😊';

                $this->sendMessage($conversation->phone_number, $reply);
                sleep(1);
                $this->setMenuState($conversation);
                break;
        }
    }

    /**
     * ============================================
     * FLUJO DE AGENDAMIENTO DE CITAS
     * ============================================
     */
    protected function startAppointmentFlow(Conversation $conversation)
    {
        $conversation->update(['bot_state' => self::STATE_ASK_APPOINTMENT_DATE]);

        $reply = "📅 *¡Agendemos tu cita!*\n\n";
        $reply .= "Cuéntame, ¿para qué *fecha* te gustaría programar tu visita?\n\n";
        $reply .= "📝 _Formato: DD/MM/AAAA_\n";
        $reply .= "📌 _Ejemplo: 15/12/2026_\n\n";
        
        $clinicaHorario = Configuration::get('clinica_horario');
        if ($clinicaHorario) {
            $reply .= "⏰ *Horario de atención:* {$clinicaHorario}";
        }

        $this->sendMessage($conversation->phone_number, $reply);
    }

    protected function handleAskAppointmentDate(Conversation $conversation, string $messageText)
    {
        // Validar formato de fecha
        if (! preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $messageText)) {
            $reply = "📅 *Formato de fecha incorrecto.*\n\n";
            $reply .= "Por favor, escribe la fecha en formato *DD/MM/AAAA*.\n";
            $reply .= '📌 _Ejemplo: 15/12/2026_';

            $this->sendMessage($conversation->phone_number, $reply);

            return;
        }

        try {
            $date = Carbon::createFromFormat('d/m/Y', $messageText);

            // Validar que sea una fecha futura
            if ($date->isPast()) {
                $reply = "⏰ *¡Ups! Esa fecha ya pasó.*\n\n";
                $reply .= "Por favor, elige una fecha futura para tu cita.\n";
                $reply .= '📅 _Recuerda: DD/MM/AAAA_';

                $this->sendMessage($conversation->phone_number, $reply);

                return;
            }

            // Validar que no sea domingo
            if ($date->isSunday()) {
                $reply = "😔 *Los domingos no laboramos.*\n\n";
                $reply .= "Nuestra clínica atiende de *Lunes a Sábado*.\n";
                $reply .= '¿Podrías elegir otra fecha? 📅';

                $this->sendMessage($conversation->phone_number, $reply);

                return;
            }

            // Validar que no sea un feriado (básico)
            $feriados = ['01/01', '01/05', '28/07', '29/07', '25/12'];
            if (in_array($date->format('d/m'), $feriados)) {
                $reply = "🎌 *Esa fecha es feriado nacional.*\n\n";
                $reply .= 'La clínica no atiende ese día. ¿Podrías elegir otra fecha? 📅';

                $this->sendMessage($conversation->phone_number, $reply);

                return;
            }

            $botData = $conversation->bot_data ?? [];
            $botData['appointment'] = ['date' => $messageText];

            $conversation->update([
                'bot_state' => self::STATE_ASK_APPOINTMENT_TIME,
                'bot_data' => $botData,
            ]);

            $reply = "✅ *Fecha registrada:* {$date->format('d/m/Y')} ({$this->getDayName($date)})\n\n";
            $reply .= "Ahora dime, ¿a qué *hora* te gustaría venir? ⏰\n\n";
            $reply .= "📝 _Formato: HH:MM AM/PM_\n";
            $reply .= '📌 _Ejemplo: 10:30 AM o 16:00_';

            $this->sendMessage($conversation->phone_number, $reply);
        } catch (\Exception $e) {
            $reply = "❌ *Fecha no válida.*\n\n";
            $reply .= "Por favor, verifica que la fecha sea correcta.\n";
            $reply .= '📌 _Ejemplo: 15/12/2026_';

            $this->sendMessage($conversation->phone_number, $reply);
        }
    }

    protected function handleAskAppointmentTime(Conversation $conversation, string $messageText)
    {
        // Validar formato de hora
        $timeFormats = [
            '/^\d{1,2}:\d{2}\s*(?:AM|PM|am|pm)$/',
            '/^\d{1,2}:\d{2}$/',
        ];

        $valid = false;
        foreach ($timeFormats as $format) {
            if (preg_match($format, $messageText)) {
                $valid = true;
                break;
            }
        }

        if (! $valid) {
            $reply = "⏰ *Formato de hora incorrecto.*\n\n";
            $reply .= "Por favor, escribe la hora en formato *HH:MM*.\n";
            $reply .= '📌 _Ejemplo: 10:30 AM o 16:00_';

            $this->sendMessage($conversation->phone_number, $reply);

            return;
        }

        $botData = $conversation->bot_data;
        $botData['appointment']['time'] = strtoupper($messageText);

        $conversation->update([
            'bot_state' => self::STATE_ASK_APPOINTMENT_REASON,
            'bot_data' => $botData,
        ]);

        $reply = "✅ *Hora registrada:* {$messageText}\n\n";
        $reply .= "Por último, ¿cuál es el *motivo* de tu consulta? 🦷\n\n";
        $reply .= "📋 *Opciones:*\n";
        $reply .= "• Revisión general\n";
        $reply .= "• Limpieza dental\n";
        $reply .= "• Dolor o molestia\n";
        $reply .= "• Ortodoncia / Brackets\n";
        $reply .= "• Otro (escribe el motivo)\n\n";
        $reply .= '_Puedes describirlo con tus propias palabras._ ✍️';

        $this->sendMessage($conversation->phone_number, $reply);
    }

    protected function handleAskAppointmentReason(Conversation $conversation, string $messageText)
    {
        $botData = $conversation->bot_data;
        $date = $botData['appointment']['date'];
        $time = $botData['appointment']['time'];
        $reason = $messageText;

        try {
            $appointmentDate = Carbon::createFromFormat('d/m/Y', $date)->format('Y-m-d');
            $requestedCarbonTime = Carbon::parse($time);

            // Obtener el primer dentista disponible (o asignar uno por defecto)
            $defaultDentist = User::role('Dentista')->first() ?? User::first();
            $defaultDentistId = $defaultDentist ? $defaultDentist->id : 1;

            // Validar Horario
            $dayOfWeek = Carbon::parse($appointmentDate)->dayOfWeekIso;
            $schedule = DoctorSchedule::where('user_id', $defaultDentistId)
                ->where('day_of_week', $dayOfWeek)
                ->where('is_working', true)
                ->first();

            if ($schedule) {
                $start = Carbon::parse($schedule->start_time);
                $end = Carbon::parse($schedule->end_time);

                // Fuera de horario
                if ($requestedCarbonTime->lt($start) || $requestedCarbonTime->gt($end)) {
                    $reply = "⏰ *Esa hora está fuera de nuestro horario de atención.*\n\n";
                    $reply .= "El doctor atiende de {$schedule->start_time} a {$schedule->end_time}.\n";
                    $reply .= 'Por favor, escribe otra hora. ⏰';
                    $conversation->update(['bot_state' => self::STATE_ASK_APPOINTMENT_TIME]);
                    $this->sendMessage($conversation->phone_number, $reply);

                    return;
                }

                // En horario de refrigerio
                if ($schedule->break_start && $schedule->break_end) {
                    $breakStart = Carbon::parse($schedule->break_start);
                    $breakEnd = Carbon::parse($schedule->break_end);
                    if ($requestedCarbonTime->between($breakStart, $breakEnd)) {
                        $reply = "🍽️ *A esa hora el doctor se encuentra en horario de refrigerio.*\n\n";
                        $reply .= "El refrigerio es de {$schedule->break_start} a {$schedule->break_end}.\n";
                        $reply .= 'Por favor, escoge otra hora. ⏰';
                        $conversation->update(['bot_state' => self::STATE_ASK_APPOINTMENT_TIME]);
                        $this->sendMessage($conversation->phone_number, $reply);

                        return;
                    }
                }
            }

            // Crear la cita
            $appointment = Appointment::create([
                'patient_id' => $conversation->patient_id,
                'dentist_id' => $defaultDentistId,
                'date' => $appointmentDate,
                'start_time' => $time,
                'duration' => 30,
                'treatment' => $reason,
                'status' => 'pending',
                'room' => $defaultDentist->room ?? 'Consultorio General',
                'notes' => 'Agendado vía WhatsApp Bot',
            ]);

            $conversation->update([
                'bot_state' => self::STATE_MAIN_MENU,
                'bot_data' => null,
            ]);

            $dateFormatted = Carbon::parse($appointmentDate)->format('d/m/Y');

            $reply = "🎉✨ *¡Cita pre-agendada exitosamente!* ✨🎉\n\n";
            $reply .= "📋 *Resumen de tu cita:*\n";
            $reply .= "📅 *Fecha:* {$dateFormatted}\n";
            $reply .= "⏰ *Hora:* {$time}\n";
            $reply .= "🦷 *Motivo:* {$reason}\n";
            $reply .= "📌 *Estado:* Pendiente de confirmación\n\n";
            $reply .= "Nuestro equipo revisará la disponibilidad y te confirmará pronto. ✅\n";
            $reply .= "Recibirás un recordatorio antes de tu cita. ⏰\n\n";
            $reply .= '¿Necesitas algo más? 😊';

            $this->sendMessage($conversation->phone_number, $reply);
            sleep(2);
            $this->setMenuState($conversation);
        } catch (\Exception $e) {
            Log::error('Error al crear cita: '.$e->getMessage());

            $reply = "😔 *Tuvimos un problema al agendar tu cita.*\n\n";
            $reply .= 'Por favor, intenta de nuevo más tarde o solicita hablar con un asesor (opción 9). 👩‍💼';

            $this->sendMessage($conversation->phone_number, $reply);
            $conversation->update(['bot_state' => self::STATE_MAIN_MENU, 'bot_data' => null]);
        }
    }

    /**
     * ============================================
     * MOSTRAR CITAS
     * ============================================
     */
    protected function showAppointments(Conversation $conversation)
    {
        $appointments = Appointment::where('patient_id', $conversation->patient_id)
            ->where('date', '>=', now()->format('Y-m-d'))
            ->orderBy('date', 'asc')
            ->orderBy('start_time', 'asc')
            ->get();

        if ($appointments->isEmpty()) {
            $reply = "📋 *No tienes citas programadas próximamente.*\n\n";
            $reply .= '¿Te gustaría agendar una? Escribe *1* para hacerlo. 📅✨';

            $this->sendMessage($conversation->phone_number, $reply);
        } else {
            $msg = "📅✨ *TUS PRÓXIMAS CITAS* ✨📅\n\n";

            foreach ($appointments as $index => $apt) {
                $dateFormatted = Carbon::parse($apt->date)->format('d/m/Y');
                $dayName = $this->getDayName(Carbon::parse($apt->date));

                $statusEmoji = [
                    'confirmed' => '✅ Confirmada',
                    'pending' => '⏳ Por confirmar',
                    'cancelled' => '❌ Cancelada',
                    'completed' => '🌟 Completada',
                ];

                $status = $statusEmoji[$apt->status] ?? $apt->status;

                $msg .= '*Cita #'.($index + 1)."*\n";
                $msg .= "📅 {$dateFormatted} ({$dayName})\n";
                $msg .= "⏰ {$apt->start_time}\n";
                $msg .= "🦷 {$apt->treatment}\n";
                $msg .= "📌 {$status}\n";

                if ($apt->status === 'pending') {
                    $msg .= "💡 _Escribe *5* para confirmar esta cita_\n";
                }

                $msg .= "─────────────────\n";
            }

            $this->sendMessage($conversation->phone_number, $msg);
        }

        sleep(2);
        $this->setMenuState($conversation);
    }

    /**
     * ============================================
     * MOSTRAR DEUDAS
     * ============================================
     */
    protected function showDebts(Conversation $conversation)
    {
        // Pagos sueltos
        $payments = Payment::where('patient_id', $conversation->patient_id)
            ->whereIn('status', ['pending', 'Pendiente'])
            ->get();

        // Contratos de Tratamiento
        $contracts = TreatmentContract::where('patient_id', $conversation->patient_id)->get()
            ->filter(function ($contract) {
                return $contract->balance_due > 0;
            });

        if ($payments->isEmpty() && $contracts->isEmpty()) {
            $reply = "🎉✨ *¡Excelentes noticias!* ✨🎉\n\n";
            $reply .= "No tienes pagos pendientes con nosotros. 💰\n";
            $reply .= '¡Tu sonrisa está al día! 🦷💙';

            $this->sendMessage($conversation->phone_number, $reply);
        } else {
            $total = $payments->sum('amount') + $contracts->sum('balance_due');
            $count = $payments->count() + $contracts->count();

            $msg = "💰 *ESTADO DE CUENTA* 💰\n\n";
            $msg .= "Tienes *{$count}* deuda(s) pendiente(s):\n\n";

            foreach ($payments as $payment) {
                $description = $payment->notes ? 'Pago: '.substr($payment->notes, 0, 30) : 'Consulta/Tratamiento';
                $dateStr = $payment->created_at ? Carbon::parse($payment->created_at)->format('d/m/Y') : date('d/m/Y');
                $msg .= "📋 {$description}\n";
                $msg .= "💵 Monto: S/ {$payment->amount}\n";
                $msg .= "📅 Generado: {$dateStr}\n";
                $msg .= "─────────────────\n";
            }

            foreach ($contracts as $contract) {
                $dateStr = $contract->start_date ? Carbon::parse($contract->start_date)->format('d/m/Y') : date('d/m/Y');
                $msg .= "📄 Contrato: {$contract->treatment_name}\n";
                $msg .= "💵 Pendiente: S/ {$contract->balance_due} (de S/ {$contract->total_cost})\n";
                $msg .= "📅 Iniciado: {$dateStr}\n";
                $msg .= "─────────────────\n";
            }

            $yapePlin = Configuration::get('pago_yape_plin', 'No configurado');
            $transferencia = Configuration::get('pago_transferencia', 'No configurado');

            $msg .= "\n💰 *TOTAL PENDIENTE: S/ {$total}*\n\n";
            $msg .= "💳 *Métodos de pago:*\n";
            if (!empty($yapePlin) && $yapePlin !== 'No configurado') {
                $msg .= "• Yape/Plin: {$yapePlin}\n";
            }
            if (!empty($transferencia) && $transferencia !== 'No configurado') {
                $msg .= "• Transferencia:\n{$transferencia}\n";
            }
            $msg .= "• Pago en clínica: Efectivo o tarjeta\n\n";
            $msg .= 'Para más información, escribe *9* y un asesor te ayudará. 👩‍💼';

            $this->sendMessage($conversation->phone_number, $msg);
        }

        sleep(2);
        $this->setMenuState($conversation);
    }

    protected function showPaymentHistory(Conversation $conversation)
    {
        $payments = Payment::where('patient_id', $conversation->patient_id)
            ->where('status', 'Pagado')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        if ($payments->isEmpty()) {
            $reply = "🧾 *Historial de Pagos* 🧾\n\n";
            $reply .= 'Actualmente no tienes pagos registrados con nosotros. 🤷‍♂️';
            $this->sendMessage($conversation->phone_number, $reply);
        } else {
            $msg = "🧾 *TUS ÚLTIMOS PAGOS (TICKETS)* 🧾\n\n";
            $msg .= "Aquí tienes el detalle de tus últimos 5 abonos:\n\n";

            foreach ($payments as $payment) {
                $dateStr = $payment->created_at ? Carbon::parse($payment->created_at)->format('d/m/Y h:i A') : date('d/m/Y');
                $methodStr = ucfirst($payment->payment_method ?? 'Efectivo');
                $descStr = $payment->notes ? substr($payment->notes, 0, 40) : 'Abono / Tratamiento';

                $msg .= "📄 *Ticket #PD-{$payment->id}*\n";
                $msg .= "📅 Fecha: {$dateStr}\n";
                $msg .= "💵 Monto Pagado: *S/ {$payment->amount}*\n";
                $msg .= "💳 Método: {$methodStr}\n";
                $msg .= "📋 Detalle: {$descStr}\n";
                $msg .= "─────────────────\n";
            }

            $msg .= "\n💡 _Este mensaje sirve como constancia (Ticket Virtual) de tus pagos._\n";
            $msg .= "📥 *Si necesitas el documento en PDF, responde con la palabra TICKET seguida del número (Ej: TICKET {$payments->first()->id}).*";
            $this->sendMessage($conversation->phone_number, $msg);
        }

        sleep(2);
        $this->setMenuState($conversation);
    }

    protected function sendPaymentPdf(Conversation $conversation, $paymentId)
    {
        $payment = Payment::where('patient_id', $conversation->patient_id)
            ->where('id', $paymentId)
            ->first();

        if (! $payment) {
            $this->sendMessage($conversation->phone_number, '❌ No encontré un ticket de pago válido con ese número para tu cuenta.');

            return;
        }

        $this->sendMessage($conversation->phone_number, '⏳ Generando tu comprobante en PDF, un momento por favor...');

        try {
            $pdfService = app(PdfGeneratorService::class);
            $pdf = $pdfService->generarComprobante($payment);
            $base64 = $pdf->base64();
            $fileName = "comprobante_pago_{$payment->id}.pdf";

            $whatsappService = app(WhatsAppService::class);
            $success = $whatsappService->enviarDocumento(
                $conversation->phone_number,
                $base64,
                $fileName,
                "🧾 Aquí tienes tu comprobante de pago #PD-{$payment->id}."
            );

            if (! $success) {
                $this->sendMessage($conversation->phone_number, '❌ Hubo un error al enviar el archivo PDF. Intenta más tarde.');
            }
        } catch (\Exception $e) {
            Log::error('Error enviando PDF por Bot: '.$e->getMessage());
            $this->sendMessage($conversation->phone_number, '❌ Ocurrió un error al generar tu comprobante.');
        }

        sleep(2);
        $this->setMenuState($conversation);
    }

    /**
     * ============================================
     * FLUJO DE REPROGRAMACIÓN
     * ============================================
     */
    protected function showRescheduleOptions(Conversation $conversation)
    {
        $appointments = Appointment::where('patient_id', $conversation->patient_id)
            ->where('date', '>=', now()->format('Y-m-d'))
            ->whereIn('status', ['confirmed', 'pending'])
            ->orderBy('date', 'asc')
            ->get();

        if ($appointments->isEmpty()) {
            $reply = "📋 *No tienes citas activas para reprogramar.*\n\n";
            $reply .= '¿Te gustaría agendar una nueva? Escribe *1*. 📅';

            $this->sendMessage($conversation->phone_number, $reply);
            sleep(2);
            $this->setMenuState($conversation);

            return;
        }

        $msg = "🔄 *REPROGRAMAR CITA* 🔄\n\n";
        $msg .= "Selecciona el *número* de la cita que deseas reprogramar:\n\n";

        $botData = ['reschedule_appointments' => []];

        foreach ($appointments as $index => $apt) {
            $num = $index + 1;
            $dateFormatted = Carbon::parse($apt->date)->format('d/m/Y');
            $botData['reschedule_appointments'][$num] = $apt->id;

            $msg .= "*{$num}.* 📅 {$dateFormatted} ⏰ {$apt->start_time}\n";
            $msg .= "   🦷 {$apt->treatment}\n\n";
        }

        $msg .= '✏️ _Escribe el número de la cita a reprogramar_';

        $conversation->update([
            'bot_state' => self::STATE_RESCHEDULE_SELECT,
            'bot_data' => $botData,
        ]);

        $this->sendMessage($conversation->phone_number, $msg);
    }

    protected function handleRescheduleSelect(Conversation $conversation, string $messageText)
    {
        $botData = $conversation->bot_data;

        if (! isset($botData['reschedule_appointments'][$messageText])) {
            $reply = "🤔 *Opción no válida.*\n";
            $reply .= 'Por favor, escribe el número de la cita que deseas reprogramar.';
            $this->sendMessage($conversation->phone_number, $reply);

            return;
        }

        $appointmentId = $botData['reschedule_appointments'][$messageText];

        $botData['reschedule_appointment_id'] = $appointmentId;
        $conversation->update([
            'bot_state' => self::STATE_RESCHEDULE_DATE,
            'bot_data' => $botData,
        ]);

        $reply = "📅 *Nueva fecha para tu cita*\n\n";
        $reply .= "¿Para qué fecha te gustaría reprogramar?\n\n";
        $reply .= "📝 _Formato: DD/MM/AAAA_\n";
        $reply .= '📌 _Ejemplo: 15/12/2026_';

        $this->sendMessage($conversation->phone_number, $reply);
    }

    protected function handleRescheduleDate(Conversation $conversation, string $messageText)
    {
        if (! preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $messageText)) {
            $reply = "📅 *Formato de fecha incorrecto.*\n\n";
            $reply .= "Por favor, escribe la fecha en formato *DD/MM/AAAA*.\n";
            $reply .= '📌 _Ejemplo: 15/12/2026_';

            $this->sendMessage($conversation->phone_number, $reply);

            return;
        }

        $botData = $conversation->bot_data;
        $botData['reschedule_date'] = $messageText;

        $conversation->update([
            'bot_state' => self::STATE_RESCHEDULE_TIME,
            'bot_data' => $botData,
        ]);

        $reply = "⏰ *Nueva hora para tu cita*\n\n";
        $reply .= "¿A qué hora te gustaría venir?\n\n";
        $reply .= "📝 _Formato: HH:MM AM/PM_\n";
        $reply .= '📌 _Ejemplo: 10:30 AM_';

        $this->sendMessage($conversation->phone_number, $reply);
    }

    protected function handleRescheduleTime(Conversation $conversation, string $messageText)
    {
        $botData = $conversation->bot_data;

        // Actualizar la cita
        $appointment = Appointment::find($botData['reschedule_appointment_id']);

        if ($appointment) {
            $appointment->update([
                'date' => Carbon::createFromFormat('d/m/Y', $botData['reschedule_date'])->format('Y-m-d'),
                'start_time' => strtoupper($messageText),
                'status' => 'pending',
            ]);
        }

        $conversation->update([
            'bot_state' => self::STATE_MAIN_MENU,
            'bot_data' => null,
        ]);

        $reply = "✅✨ *¡Cita reprogramada exitosamente!*\n\n";
        $reply .= "📅 Nueva fecha: {$botData['reschedule_date']}\n";
        $reply .= '⏰ Nueva hora: '.strtoupper($messageText)."\n\n";
        $reply .= 'Te enviaremos un recordatorio antes de tu cita. ⏰';

        $this->sendMessage($conversation->phone_number, $reply);
        sleep(2);
        $this->setMenuState($conversation);
    }

    /**
     * ============================================
     * CONFIRMACIÓN DE CITAS PENDIENTES
     * ============================================
     */
    protected function showPendingConfirmations(Conversation $conversation)
    {
        $appointments = Appointment::where('patient_id', $conversation->patient_id)
            ->where('date', '>=', now()->format('Y-m-d'))
            ->where('status', 'pending')
            ->orderBy('date', 'asc')
            ->get();

        if ($appointments->isEmpty()) {
            $reply = "✅ *No tienes citas pendientes de confirmar.*\n\n";
            $reply .= '¡Todo está al día! 😊';

            $this->sendMessage($conversation->phone_number, $reply);
            sleep(2);
            $this->setMenuState($conversation);

            return;
        }

        $msg = "📋 *CITAS PENDIENTES DE CONFIRMAR* 📋\n\n";
        $msg .= "Selecciona el número de la cita que deseas confirmar:\n\n";

        $botData = ['confirm_appointments' => []];

        foreach ($appointments as $index => $apt) {
            $num = $index + 1;
            $dateFormatted = Carbon::parse($apt->date)->format('d/m/Y');
            $botData['confirm_appointments'][$num] = $apt->id;

            $msg .= "*{$num}.* 📅 {$dateFormatted} ⏰ {$apt->start_time}\n";
            $msg .= "   🦷 {$apt->treatment}\n\n";
        }

        $msg .= '✏️ _Escribe el número de la cita a confirmar_';

        $conversation->update([
            'bot_state' => self::STATE_CONFIRM_APPOINTMENT,
            'bot_data' => $botData,
        ]);

        $this->sendMessage($conversation->phone_number, $msg);
    }

    protected function handleConfirmAppointment(Conversation $conversation, string $messageText)
    {
        $botData = $conversation->bot_data;

        if (! isset($botData['confirm_appointments'][$messageText])) {
            $reply = "🤔 *Opción no válida.*\n";
            $reply .= 'Por favor, escribe el número de la cita que deseas confirmar.';
            $this->sendMessage($conversation->phone_number, $reply);

            return;
        }

        $appointmentId = $botData['confirm_appointments'][$messageText];
        $appointment = Appointment::find($appointmentId);

        if ($appointment) {
            $appointment->update(['status' => 'confirmed']);
            $dateFormatted = Carbon::parse($appointment->date)->format('d/m/Y');

            $reply = "✅✨ *¡Cita confirmada exitosamente!*\n\n";
            $reply .= "📅 Fecha: {$dateFormatted}\n";
            $reply .= "⏰ Hora: {$appointment->start_time}\n";
            $reply .= "🦷 Motivo: {$appointment->treatment}\n\n";
            $reply .= '¡Te esperamos! 😊🦷';
        } else {
            $reply = "😔 *No se encontró la cita.*\n";
            $reply .= 'Por favor, intenta de nuevo más tarde.';
        }

        $conversation->update([
            'bot_state' => self::STATE_MAIN_MENU,
            'bot_data' => null,
        ]);

        $this->sendMessage($conversation->phone_number, $reply);
        sleep(2);
        $this->setMenuState($conversation);
    }

    /**
     * ============================================
     * PROMOCIONES
     * ============================================
     */
    protected function showPromotions(Conversation $conversation)
    {
        $promotions = Promotion::where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', now()->format('Y-m-d'));
            })
            ->get();

        $msg = "🦷✨ *PROMOCIONES ESPECIALES* ✨🦷\n\n";

        if ($promotions->isEmpty()) {
            $msg .= "Actualmente no tenemos promociones activas, ¡pero vuelve pronto para ver nuestras novedades!\n\n";
        } else {
            $msg .= "¡Tenemos ofertas increíbles para ti!\n\n";
            foreach ($promotions as $promo) {
                $msg .= "🎉 *{$promo->title}*\n";
                if ($promo->description) {
                    $msg .= "• {$promo->description}\n";
                }
                if ($promo->discount_value) {
                    $discount = $promo->discount_type === 'percentage'
                        ? (int) $promo->discount_value.'% desc.'
                        : 'S/ '.$promo->discount_value.' de desc.';
                    $msg .= "• {$discount}\n";
                }
                $msg .= "\n";
            }
        }

        $msg .= 'Para agendar tu cita con estas promociones, escribe *1* o *9* para hablar con un asesor. 💙';

        $this->sendMessage($conversation->phone_number, $msg);
        sleep(2);
        $this->setMenuState($conversation);
    }

    /**
     * ============================================
     * FLUJO DE SORTEOS
     * ============================================
     */
    protected function showRaffles(Conversation $conversation)
    {
        $raffles = Raffle::where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('draw_date')
                    ->orWhere('draw_date', '>=', now()->format('Y-m-d'));
            })
            ->get();

        if ($raffles->isEmpty()) {
            $msg = "🎁 *SORTEOS VIGENTES*\n\n";
            $msg .= 'Actualmente no tenemos sorteos activos. ¡Mantente atento a nuestras redes para futuras novedades! 🥳';
            $this->sendMessage($conversation->phone_number, $msg);
            sleep(2);
            $this->setMenuState($conversation);

            return;
        }

        $msg = "🎁 *SORTEOS VIGENTES* 🎁\n\n";
        $msg .= "¡Participa y gana increíbles premios!\n\n";

        foreach ($raffles as $index => $raffle) {
            $msg .= '*'.($index + 1).".* {$raffle->name}\n";
            if ($raffle->description) {
                $msg .= "   _{$raffle->description}_\n";
            }
        }

        $msg .= "\n✍️ Escribe el *NÚMERO* del sorteo en el que deseas participar (Ejemplo: 1).\n";
        $msg .= "Escribe *'salir'* para cancelar.";

        $conversation->update(['bot_state' => self::STATE_RAFFLE_JOIN]);
        $this->sendMessage($conversation->phone_number, $msg);
    }

    protected function handleRaffleJoin(Conversation $conversation, string $messageText)
    {
        $normalizedText = strtolower(trim($messageText));

        if (in_array($normalizedText, ['salir', 'cancelar', 'volver'])) {
            $this->sendMessage($conversation->phone_number, 'Volviendo al menú principal... 🔙');
            sleep(1);
            $this->setMenuState($conversation);

            return;
        }

        $raffles = Raffle::where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('draw_date')
                    ->orWhere('draw_date', '>=', now()->format('Y-m-d'));
            })
            ->get();

        $index = (int) $messageText - 1;

        if ($index >= 0 && $index < $raffles->count()) {
            $raffle = $raffles[$index];

            // Verificar si ya está participando
            $exists = RaffleParticipant::where('raffle_id', $raffle->id)
                ->where('phone_number', $conversation->phone_number)
                ->exists();

            if ($exists) {
                $reply = "😅 ¡Ya estás participando en el sorteo *{$raffle->name}*!\n\nTe deseamos mucha suerte. 🍀";
            } else {
                RaffleParticipant::create([
                    'raffle_id' => $raffle->id,
                    'patient_id' => $conversation->patient_id,
                    'phone_number' => $conversation->phone_number,
                ]);
                $reply = "🎉 *¡Inscripción Exitosa!* 🎉\n\n";
                $reply .= "Estás participando en el sorteo: *{$raffle->name}*.\n";
                $reply .= 'Te avisaremos por este medio si resultas ganador. ¡Mucha suerte! 🍀';
            }

            $this->sendMessage($conversation->phone_number, $reply);
            sleep(2);
            $this->setMenuState($conversation);
        } else {
            $this->sendMessage($conversation->phone_number, "Número inválido. Escribe el número correspondiente al sorteo, o *'salir'* para volver.");
        }
    }

    /**
     * ============================================
     * TRANSFERIR A HUMANO
     * ============================================
     */
    protected function transferToHuman(Conversation $conversation)
    {
        $conversation->update(['bot_status' => 'human_assigned']);

        $reply = "👩‍💼 *Te estoy conectando con un asesor...*\n\n";
        $reply .= "Un miembro de nuestro equipo te atenderá en breve. 💙\n";
        $reply .= "⏰ *Tiempo estimado de espera:* 2-5 minutos.\n\n";
        $reply .= 'Gracias por tu paciencia. 😊✨';

        $this->sendMessage($conversation->phone_number, $reply);
    }

    /**
     * ============================================
     * FLUJO DE VALORACIÓN
     * ============================================
     */
    protected function startRatingFlow(Conversation $conversation)
    {
        // Verificar citas completadas recientemente (últimos 30 días)
        $completedAppointment = Appointment::where('patient_id', $conversation->patient_id)
            ->where('status', 'completed')
            ->where('date', '>=', now()->subDays(30)->format('Y-m-d'))
            ->exists();

        if (! $completedAppointment) {
            $reply = "⭐ *Valoración de servicio*\n\n";
            $reply .= "Para valorar nuestro servicio, necesitas haber tenido una cita recientemente.\n";
            $reply .= '¿Te gustaría agendar una? Escribe *1*. 📅';

            $this->sendMessage($conversation->phone_number, $reply);
            sleep(2);
            $this->setMenuState($conversation);

            return;
        }

        $conversation->update(['bot_state' => self::STATE_ASK_RATING]);

        $reply = "⭐✨ *¡Valóranos!* ✨⭐\n\n";
        $reply .= "Nos encantaría conocer tu opinión sobre nuestro servicio. 🤗\n\n";
        $reply .= "En una escala del *1 al 5*, donde 5 es excelente:\n";
        $reply .= "¿Cómo calificarías tu experiencia en {$this->clinicName}?\n\n";
        $reply .= "1️⃣ ⭐ Muy mala\n";
        $reply .= "2️⃣ ⭐⭐ Regular\n";
        $reply .= "3️⃣ ⭐⭐⭐ Buena\n";
        $reply .= "4️⃣ ⭐⭐⭐⭐ Muy buena\n";
        $reply .= "5️⃣ ⭐⭐⭐⭐⭐ Excelente\n\n";
        $reply .= '✏️ _Escribe un número del 1 al 5_';

        $this->sendMessage($conversation->phone_number, $reply);
    }

    protected function handleAskRating(Conversation $conversation, string $messageText)
    {
        $ratingScore = intval($messageText);

        if ($ratingScore < 1 || $ratingScore > 5) {
            $reply = "🤔 Por favor, califica del *1 al 5*.\n";
            $reply .= 'Siendo 1⭐ la más baja y 5⭐⭐⭐⭐⭐ la más alta.';
            $this->sendMessage($conversation->phone_number, $reply);

            return;
        }

        // Guardar la valoración en la base de datos INMEDIATAMENTE
        $rating = null;
        try {
            $rating = Rating::create([
                'patient_id' => $conversation->patient_id,
                'score' => $ratingScore,
                'source' => 'whatsapp_bot',
            ]);
        } catch (\Exception $e) {
            Log::error('Error al guardar rating: '.$e->getMessage());
        }

        $botData = $conversation->bot_data ?? [];
        $botData['rating_id'] = $rating ? $rating->id : null;
        $botData['rating'] = $ratingScore;

        $ratingMessages = [
            1 => '😔 Lamentamos mucho que tu experiencia haya sido muy mala.',
            2 => '😟 Sentimos que tu experiencia solo haya sido regular.',
            3 => '🙂 ¡Gracias! Nos alegra que tu experiencia haya sido buena.',
            4 => '😊 ¡Qué bien! Nos encanta que hayas tenido una muy buena experiencia.',
            5 => '🌟 ¡Excelente! ¡Nos hace muy felices que tu experiencia haya sido de 5 estrellas!',
        ];

        $reply = $ratingMessages[$ratingScore]."\n\n";

        if ($ratingScore <= 3) {
            $reply .= "¿Nos ayudarías respondiendo brevemente: *¿Qué debemos mejorar? o ¿Cuál fue el motivo de tu calificación?* ✍️\n";
            $reply .= '_Cuéntanos en pocas palabras tu experiencia para poder mejorar..._';
        } else {
            $reply .= "¿Te gustaría dejarnos un comentario adicional o retroalimentación? ✍️\n";
            $reply .= "_Escribe tu comentario o *'salir'* para volver al menú._";
        }

        $conversation->update([
            'bot_state' => self::STATE_ASK_RATING_COMMENT,
            'bot_data' => $botData,
        ]);

        $this->sendMessage($conversation->phone_number, $reply);
    }

    protected function handleAskRatingComment(Conversation $conversation, string $messageText)
    {
        $botData = $conversation->bot_data;
        $ratingId = $botData['rating_id'] ?? null;

        $normalizedText = strtolower(trim($messageText));

        if (! in_array($normalizedText, ['salir', 'cancelar'])) {
            // Actualizar la valoración con el comentario si existe
            if ($ratingId) {
                try {
                    $rating = Rating::find($ratingId);
                    if ($rating) {
                        $rating->update(['comment' => trim($messageText)]);
                    }
                } catch (\Exception $e) {
                    Log::error('Error al actualizar comentario de rating: '.$e->getMessage());
                }
            }
        }

        $conversation->update([
            'bot_state' => self::STATE_MAIN_MENU,
            'bot_data' => null,
        ]);

        $reply = "🙏✨ *¡Muchas gracias por tu retroalimentación!* ✨🙏\n\n";
        $reply .= "Tu opinión nos ayuda enormemente a mejorar cada día. 💙\n";
        $reply .= 'Seguimos trabajando para darte la mejor atención dental. 🦷';

        $this->sendMessage($conversation->phone_number, $reply);
        sleep(2);
        $this->setMenuState($conversation);
    }

    /**
     * ============================================
     * NOTIFICACIONES AUTOMÁTICAS
     * ============================================
     */

    /**
     * Verifica y envía notificaciones pendientes al iniciar conversación
     */
    protected function checkPendingNotifications(Conversation $conversation, $patient)
    {
        // Verificar cumpleaños
        $this->checkBirthday($conversation, $patient);

        // Verificar citas del día siguiente (recordatorio 24h)
        $this->checkAppointmentReminder24h($conversation, $patient);

        // Verificar citas próximas (recordatorio 2h)
        $this->checkAppointmentReminder2h($conversation, $patient);

        // Verificar citas pendientes de confirmación
        $this->checkPendingConfirmation($conversation, $patient);

        // Verificar campaña de limpieza (6 meses)
        $this->checkCleaningCampaign($conversation, $patient);

        // Verificar pagos pendientes
        $this->checkPaymentReminder($conversation, $patient);

        // Verificar si debe valoración
        $this->checkRatingRequest($conversation, $patient);
    }

    public function checkBirthday(Conversation $conversation, $patient)
    {
        if (! $patient->date_of_birth) {
            return;
        }

        $birthday = Carbon::parse($patient->date_of_birth);
        $today = now();

        // Si hoy es su cumpleaños
        if ($birthday->month === $today->month && $birthday->day === $today->day) {
            $age = $today->year - $birthday->year;

            $msg = "🎂🎉✨ *¡FELIZ CUMPLEAÑOS!* ✨🎉🎂\n\n";
            $msg .= "¡Felices {$age} años, *{$patient->first_name}*! 🎈\n\n";
            $msg .= "De parte de todo el equipo de *{$this->clinicName}* te deseamos un día maravilloso. 🦷💙\n\n";
            $msg .= "🎁 *Regalo especial:* 30% de descuento en tu próxima limpieza dental este mes.\n\n";
            $msg .= '¡Que tengas un año lleno de sonrisas! 😊✨';

            $this->sendMessage($conversation->phone_number, $msg);
        }
    }

    public function checkAppointmentReminder24h(Conversation $conversation, $patient)
    {
        $tomorrow = now()->addDay()->format('Y-m-d');

        $appointments = Appointment::where('patient_id', $patient->id)
            ->where('date', $tomorrow)
            ->whereIn('status', ['pending', 'confirmed'])
            ->get();

        $clinicaDireccion = Configuration::get('clinica_direccion');
        $clinicaTelefono = Configuration::get('clinica_telefono');

        foreach ($appointments as $apt) {
            $msg = "⏰ *RECORDATORIO DE CITA* ⏰\n\n";
            $msg .= "¡Hola *{$patient->first_name}*! 👋\n\n";
            $msg .= "Te recordamos que tienes una cita *mañana*:\n\n";
            $msg .= '📅 Fecha: '.Carbon::parse($apt->date)->format('d/m/Y')."\n";
            $msg .= "⏰ Hora: {$apt->start_time}\n";
            $msg .= "🦷 Motivo: {$apt->treatment}\n\n";
            if ($clinicaDireccion) {
                $msg .= "📍 *Dirección:* {$clinicaDireccion}\n";
            }
            if ($clinicaTelefono) {
                $msg .= "📞 *Teléfono:* {$clinicaTelefono}\n\n";
            } else {
                $msg .= "\n";
            }
            $msg .= "Si necesitas reprogramar, responde con la opción *4*. 🔄\n";
            $msg .= '¡Te esperamos! 😊🦷';

            $this->sendMessage($conversation->phone_number, $msg);
        }
    }

    public function checkAppointmentReminder2h(Conversation $conversation, $patient)
    {
        $today = now()->format('Y-m-d');
        $twoHoursFromNow = now()->addHours(2)->format('H:i');

        $appointments = Appointment::where('patient_id', $patient->id)
            ->where('date', $today)
            ->where('start_time', 'like', substr($twoHoursFromNow, 0, 2).'%')
            ->whereIn('status', ['pending', 'confirmed'])
            ->get();

        $clinicaDireccion = Configuration::get('clinica_direccion');

        foreach ($appointments as $apt) {
            $msg = "🔔 *TU CITA ES EN 2 HORAS* 🔔\n\n";
            $msg .= "¡Hola *{$patient->first_name}*! 🦷\n\n";
            $msg .= "Tu cita es hoy a las *{$apt->start_time}*.\n\n";
            $msg .= "📋 *Consejos:*\n";
            $msg .= "• Llega 10 minutos antes\n";
            $msg .= "• Trae tu DNI\n";
            $msg .= "• Cepíllate antes de venir 😁\n\n";
            if ($clinicaDireccion) {
                $msg .= "📍 {$clinicaDireccion}\n";
            }
            $msg .= '¡Te esperamos! 💙';

            $this->sendMessage($conversation->phone_number, $msg);
        }
    }

    public function checkPendingConfirmation(Conversation $conversation, $patient)
    {
        $appointments = Appointment::where('patient_id', $patient->id)
            ->where('date', '>=', now()->format('Y-m-d'))
            ->where('status', 'pending_confirmation')
            ->count();

        if ($appointments > 0) {
            $msg = "📋 *TIENES CITAS POR CONFIRMAR* 📋\n\n";
            $msg .= "Hola *{$patient->first_name}*, tienes *{$appointments}* cita(s) pendiente(s) de confirmación.\n\n";
            $msg .= "Escribe *5* para verlas y confirmarlas. ✅\n";
            $msg .= '¡Así aseguramos tu espacio! 😊';

            $this->sendMessage($conversation->phone_number, $msg);
        }
    }

    public function checkCleaningCampaign(Conversation $conversation, $patient)
    {
        // Verificar última limpieza dental
        $lastCleaning = Appointment::where('patient_id', $patient->id)
            ->where('treatment', 'like', '%limpieza%')
            ->where('status', 'completed')
            ->orderBy('date', 'desc')
            ->first();

        if ($lastCleaning) {
            $lastDate = Carbon::parse($lastCleaning->date);
            $monthsSince = $lastDate->diffInMonths(now());

            if ($monthsSince >= 6) {
                $msg = "🦷✨ *¡HORA DE TU LIMPIEZA DENTAL!* ✨🦷\n\n";
                $msg .= "Hola *{$patient->first_name}*, han pasado *{$monthsSince} meses* desde tu última limpieza dental.\n\n";
                $msg .= "🎉 *Promoción especial:* Limpieza dental a S/ 79.90\n\n";
                $msg .= "La limpieza regular previene caries, mal aliento y enfermedades. 🦷💙\n\n";
                $msg .= 'Escribe *1* para agendar tu cita de limpieza. 📅';

                $this->sendMessage($conversation->phone_number, $msg);
            }
        }
    }

    public function checkPaymentReminder(Conversation $conversation, $patient)
    {
        $pendingPayments = Payment::where('patient_id', $patient->id)
            ->where('status', 'pending')
            ->where('due_date', '<=', now()->addDays(3)->format('Y-m-d'))
            ->get();

        if ($pendingPayments->isNotEmpty()) {
            $total = $pendingPayments->sum('amount');

            $yapePlin = Configuration::get('pago_yape_plin', 'No configurado');
            $transferencia = Configuration::get('pago_transferencia', 'No configurado');

            $msg = "💳 *RECORDATORIO DE PAGO* 💳\n\n";
            $msg .= "Hola *{$patient->first_name}*, tienes pagos pendientes por *S/ {$total}*.\n\n";
            $msg .= "📋 *Pagos:*\n";
            foreach ($pendingPayments as $payment) {
                $msg .= "• {$payment->description} - S/ {$payment->amount} (Vence: ".Carbon::parse($payment->due_date)->format('d/m/Y').")\n";
            }
            $msg .= "\n💡 *Métodos de pago:*\n";
            if (!empty($yapePlin) && $yapePlin !== 'No configurado') {
                $msg .= "• Yape/Plin: {$yapePlin}\n";
            }
            if (!empty($transferencia) && $transferencia !== 'No configurado') {
                $msg .= "• Transferencia:\n{$transferencia}\n";
            }
            $msg .= "\nPara más información, escribe *9*. 👩‍💼";

            $this->sendMessage($conversation->phone_number, $msg);
        }
    }

    public function checkRatingRequest(Conversation $conversation, $patient)
    {
        // Verificar si tiene citas completadas sin valorar
        $unratedAppointment = Appointment::where('patient_id', $patient->id)
            ->where('status', 'completed')
            ->where('date', '>=', now()->subDays(7)->format('Y-m-d'))
            ->where('date', '<=', now()->subDays(1)->format('Y-m-d'))
            ->exists();

        $hasRated = Rating::where('patient_id', $patient->id)
            ->where('created_at', '>=', now()->subDays(7))
            ->exists();

        if ($unratedAppointment && ! $hasRated) {
            $msg = "⭐ *¿NOS AYUDAS A MEJORAR?* ⭐\n\n";
            $msg .= "Hola *{$patient->first_name}*, valoramos mucho tu opinión. 🦷💙\n\n";
            $msg .= "¿Podrías tomarte un momento para calificar tu última visita?\n";
            $msg .= "Escribe *0* para valorarnos. ⭐\n\n";
            $msg .= '¡Gracias por ayudarnos a mejorar! 😊';

            $this->sendMessage($conversation->phone_number, $msg);
        }
    }

    /**
     * Notificación de finalización de tratamiento
     */
    public function sendTreatmentCompletion($patient)
    {
        $msg = "🎉✨ *¡FELICITACIONES!* ✨🎉\n\n";
        $msg .= "¡Hola *{$patient->first_name}*! 🦷💙\n\n";
        $msg .= "Nos alegra informarte que tu tratamiento ha finalizado exitosamente. 🌟\n\n";
        $msg .= "Gracias por confiar en nosotros para cuidar tu sonrisa. 😊\n\n";
        $msg .= "📋 *Recomendaciones:*\n";
        $msg .= "• Mantén tus revisiones cada 6 meses\n";
        $msg .= "• Cepíllate 3 veces al día\n";
        $msg .= "• Usa hilo dental diariamente\n\n";
        $msg .= "Escribe *1* para agendar tu próxima revisión. 📅\n";
        $msg .= '¡Estamos aquí para ti! 💙';

        return $msg;
    }

    /**
     * ============================================
     * MÉTODOS AUXILIARES
     * ============================================
     */
    protected function sendTypingIndicator($phoneNumber)
    {
        // Simular indicador de escritura (si Evolution API lo soporta)
        // $this->evolutionApi->sendPresence($phoneNumber, 'composing');
    }

    protected function sendMessage($phoneNumber, $message)
    {
        return $this->evolutionApi->sendText($phoneNumber, $message);
    }

    protected function getDayName(Carbon $date)
    {
        $days = [
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado',
            0 => 'Domingo',
        ];

        return $days[$date->dayOfWeek] ?? '';
    }

    /**
     * Envía notificación masiva a pacientes (para campañas, promociones, etc.)
     */
    public function sendBulkNotification(array $patientIds, string $message)
    {
        $patients = Patient::whereIn('id', $patientIds)->get();
        $sent = 0;

        foreach ($patients as $patient) {
            try {
                $this->sendMessage($patient->phone, $message);
                $sent++;
                sleep(1); // Evitar rate limiting
            } catch (\Exception $e) {
                Log::error("Error enviando notificación a paciente {$patient->id}: ".$e->getMessage());
            }
        }

        return $sent;
    }

    /**
     * Programa notificaciones automáticas (para ser llamado por un cron job)
     */
    public function processScheduledNotifications()
    {
        $this->processBirthdayNotifications();
        $this->processAppointmentReminders24h();
        $this->processAppointmentReminders2h();
        $this->processCleaningCampaigns();
        $this->processPaymentReminders();
        $this->processRatingRequests();
    }

    protected function processBirthdayNotifications()
    {
        $today = now();
        $patients = Patient::whereRaw('MONTH(date_of_birth) = ? AND DAY(date_of_birth) = ?', [
            $today->month,
            $today->day,
        ])->get();

        foreach ($patients as $patient) {
            $age = $today->year - Carbon::parse($patient->date_of_birth)->year;

            $msg = "🎂🎉✨ *¡FELIZ CUMPLEAÑOS!* ✨🎉🎂\n\n";
            $msg .= "¡Felices {$age} años, *{$patient->first_name}*! 🎈\n\n";
            $msg .= "De parte de todo el equipo de *{$this->clinicName}* te deseamos un día maravilloso. 🦷💙\n\n";
            $msg .= "🎁 *Regalo especial:* 30% de descuento en tu próxima limpieza dental este mes.\n\n";
            $msg .= '¡Que tengas un año lleno de sonrisas! 😊✨';

            try {
                $this->sendMessage($patient->phone, $msg);
            } catch (\Exception $e) {
                Log::error("Error en notificación de cumpleaños para paciente {$patient->id}: ".$e->getMessage());
            }
        }
    }

    protected function processAppointmentReminders24h()
    {
        $tomorrow = now()->addDay()->format('Y-m-d');

        $appointments = Appointment::with('patient')
            ->where('date', $tomorrow)
            ->whereIn('status', ['pending', 'confirmed'])
            ->get();

        $clinicaDireccion = Configuration::get('clinica_direccion');

        foreach ($appointments as $apt) {
            $msg = "⏰ *RECORDATORIO DE CITA - MAÑANA* ⏰\n\n";
            $msg .= "¡Hola *{$apt->patient->first_name}*! 👋\n\n";
            $msg .= "Te recordamos tu cita de mañana:\n";
            $msg .= '📅 '.Carbon::parse($apt->date)->format('d/m/Y')."\n";
            $msg .= "⏰ {$apt->start_time}\n";
            $msg .= "🦷 {$apt->treatment}\n\n";
            if ($clinicaDireccion) {
                $msg .= "📍 {$clinicaDireccion}\n";
            }
            $msg .= '¡Te esperamos! 😊🦷';

            try {
                $this->sendMessage($apt->patient->phone, $msg);
            } catch (\Exception $e) {
                Log::error("Error en recordatorio 24h para cita {$apt->id}: ".$e->getMessage());
            }
        }
    }

    protected function processAppointmentReminders2h()
    {
        $today = now()->format('Y-m-d');
        $twoHoursFromNow = now()->addHours(2)->format('H:i');

        $appointments = Appointment::with('patient')
            ->where('date', $today)
            ->where('start_time', 'like', substr($twoHoursFromNow, 0, 2).'%')
            ->whereIn('status', ['pending', 'confirmed'])
            ->get();

        $clinicaDireccion = Configuration::get('clinica_direccion');

        foreach ($appointments as $apt) {
            $msg = "🔔 *TU CITA ES EN 2 HORAS* 🔔\n\n";
            $msg .= "¡Hola *{$apt->patient->first_name}*! 🦷\n\n";
            $msg .= "Tu cita es hoy a las *{$apt->start_time}*.\n";
            $msg .= "🦷 {$apt->treatment}\n\n";
            if ($clinicaDireccion) {
                $msg .= "📍 {$clinicaDireccion}\n";
            }
            $msg .= '¡Llega 10 minutos antes! 😁';

            try {
                $this->sendMessage($apt->patient->phone, $msg);
            } catch (\Exception $e) {
                Log::error("Error en recordatorio 2h para cita {$apt->id}: ".$e->getMessage());
            }
        }
    }

    protected function processCleaningCampaigns()
    {
        $sixMonthsAgo = now()->subMonths(6)->format('Y-m-d');

        $patients = Patient::whereHas('appointments', function ($query) use ($sixMonthsAgo) {
            $query->where('treatment', 'like', '%limpieza%')
                ->where('status', 'completed')
                ->where('date', '<=', $sixMonthsAgo);
        })->get();

        foreach ($patients as $patient) {
            $msg = "🦷✨ *¡HORA DE TU LIMPIEZA DENTAL!* ✨🦷\n\n";
            $msg .= "Hola *{$patient->first_name}*, ¡han pasado 6 meses desde tu última limpieza!\n\n";
            $msg .= "🎉 *Promoción:* Limpieza dental a S/ 79.90\n\n";
            $msg .= 'Responde *1* para agendar tu cita. 📅';

            try {
                $this->sendMessage($patient->phone, $msg);
            } catch (\Exception $e) {
                Log::error("Error en campaña de limpieza para paciente {$patient->id}: ".$e->getMessage());
            }
        }
    }

    protected function processPaymentReminders()
    {
        $payments = Payment::with('patient')
            ->where('status', 'pending')
            ->where('due_date', '<=', now()->addDays(3)->format('Y-m-d'))
            ->get()
            ->groupBy('patient_id');

        foreach ($payments as $patientId => $patientPayments) {
            $patient = $patientPayments->first()->patient;
            $total = $patientPayments->sum('amount');

            $msg = "💳 *RECORDATORIO DE PAGO* 💳\n\n";
            $msg .= "Hola *{$patient->first_name}*, tienes pagos pendientes por *S/ {$total}*.\n\n";
            $msg .= 'Para más información, visita nuestra clínica o responde *9* para hablar con un asesor. 👩‍💼';

            try {
                $this->sendMessage($patient->phone, $msg);
            } catch (\Exception $e) {
                Log::error("Error en recordatorio de pago para paciente {$patient->id}: ".$e->getMessage());
            }
        }
    }

    protected function processRatingRequests()
    {
        $sevenDaysAgo = now()->subDays(7)->format('Y-m-d');
        $yesterday = now()->subDay()->format('Y-m-d');

        $patients = Patient::whereHas('appointments', function ($query) use ($sevenDaysAgo, $yesterday) {
            $query->where('status', 'completed')
                ->whereBetween('date', [$sevenDaysAgo, $yesterday]);
        })->whereDoesntHave('ratings', function ($query) {
            $query->where('created_at', '>=', now()->subDays(7));
        })->get();

        foreach ($patients as $patient) {
            $msg = "⭐ *¿NOS AYUDAS A MEJORAR?* ⭐\n\n";
            $msg .= "Hola *{$patient->first_name}*, valoramos mucho tu opinión. 🦷💙\n\n";
            $msg .= '¿Cómo fue tu última visita? Responde *0* para valorarnos del 1 al 5. ⭐';

            try {
                $this->sendMessage($patient->phone, $msg);
            } catch (\Exception $e) {
                Log::error("Error en solicitud de rating para paciente {$patient->id}: ".$e->getMessage());
            }
        }
    }
}
