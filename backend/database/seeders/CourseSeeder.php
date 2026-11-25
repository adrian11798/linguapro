<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LiveClass;
use App\Models\Progress;
use App\Models\User;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $teacher = User::where('email', 'laura@linguapro.mx')->first();
        $evelyn = User::where('email', 'evelyn@linguapro.mx')->first();

        $ingles = Course::create([
            'name' => 'Inglés Avanzado C1',
            'description' => 'Perfecciona fluidez, vocabulario de negocios y comprensión auditiva a nivel avanzado.',
            'language' => 'Inglés',
            'level' => 'C1',
            'image' => 'https://images.unsplash.com/photo-1513475382585-d06e58bcb0e0?w=600&q=80',
            'status' => 'published',
            'teacher_id' => $teacher->id,
        ]);

        $francesA2 = Course::create([
            'name' => 'Francés Básico A2',
            'description' => 'Conversación cotidiana, gramática esencial y pronunciación para principiantes.',
            'language' => 'Francés',
            'level' => 'A2',
            'image' => 'https://images.unsplash.com/photo-1502602898657-3e91760cbb34?w=600&q=80',
            'status' => 'published',
            'teacher_id' => $teacher->id,
        ]);

        $francesB2 = Course::create([
            'name' => 'Francés Intermedio B2',
            'description' => 'Consolida estructuras gramaticales y amplía tu vocabulario para contextos profesionales.',
            'language' => 'Francés',
            'level' => 'B2',
            'image' => 'https://images.unsplash.com/photo-1431274172761-fca41d930114?w=600&q=80',
            'status' => 'published',
            'teacher_id' => $teacher->id,
        ]);

        Course::create([
            'name' => 'Alemán Básico A1',
            'description' => 'Tus primeros pasos en alemán: saludos, presentaciones y frases básicas.',
            'language' => 'Alemán',
            'level' => 'A1',
            'image' => 'https://images.unsplash.com/photo-1467269204594-9661b134dd2b?w=600&q=80',
            'status' => 'published',
        ]);

        $lessonsIngles = [
            ['title' => 'Vocabulario de negocios', 'description' => 'Términos y expresiones para reuniones y correos.', 'order' => 1],
            ['title' => 'Comprensión auditiva avanzada', 'description' => 'Práctica con audios reales de noticias y podcasts.', 'order' => 2],
            ['title' => 'Debate y argumentación', 'description' => 'Estructura tus ideas para defender un punto de vista.', 'order' => 3],
        ];
        foreach ($lessonsIngles as $lesson) {
            Lesson::create($lesson + ['course_id' => $ingles->id]);
        }

        $lessonsFrancesA2 = [
            ['title' => 'Saludos y presentaciones', 'description' => 'Cómo presentarte y saludar en distintos contextos.', 'order' => 1],
            ['title' => 'Pronunciación avanzada', 'description' => 'Sonidos difíciles del francés y cómo dominarlos.', 'order' => 2],
        ];
        foreach ($lessonsFrancesA2 as $lesson) {
            Lesson::create($lesson + ['course_id' => $francesA2->id]);
        }

        $lessonsFrancesB2 = [
            ['title' => 'Tiempos verbales compuestos', 'description' => 'Passé composé e imparfait en contexto.', 'order' => 1],
            ['title' => 'Francés para el trabajo', 'description' => 'Vocabulario y frases para el entorno profesional.', 'order' => 2],
        ];
        foreach ($lessonsFrancesB2 as $lesson) {
            Lesson::create($lesson + ['course_id' => $francesB2->id]);
        }

        // Inscripciones y progreso de ejemplo para Evelyn (RF-07, RF-09)
        Enrollment::create(['user_id' => $evelyn->id, 'course_id' => $ingles->id, 'enrolled_at' => now(), 'status' => 'active']);
        Enrollment::create(['user_id' => $evelyn->id, 'course_id' => $francesA2->id, 'enrolled_at' => now(), 'status' => 'active']);

        $firstIngles = $ingles->lessons()->orderBy('order')->first();
        $firstFrances = $francesA2->lessons()->orderBy('order')->first();

        Progress::create(['user_id' => $evelyn->id, 'lesson_id' => $firstIngles->id, 'completed' => true, 'completed_at' => now()]);
        Progress::create(['user_id' => $evelyn->id, 'lesson_id' => $firstFrances->id, 'completed' => true, 'completed_at' => now()]);

        // Clases en vivo (RF-10/RF-11)
        LiveClass::create([
            'course_id' => $francesA2->id,
            'title' => 'Clase de Francés A2 - Pronunciación Avanzada',
            'date' => now()->toDateString(),
            'time' => '16:30',
            'meeting_url' => 'https://meet.google.com/example-1',
            'status' => 'scheduled',
        ]);

        LiveClass::create([
            'course_id' => $ingles->id,
            'title' => 'Clase de Inglés C1 - Conversación de Negocios',
            'date' => now()->toDateString(),
            'time' => '18:00',
            'meeting_url' => 'https://meet.google.com/example-2',
            'status' => 'scheduled',
        ]);
    }
}
