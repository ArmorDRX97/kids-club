<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\Child;
use App\Models\Enrollment;
use App\Models\Package;
use App\Models\Payment;
use App\Models\ReceptionSetting;
use App\Models\Room;
use App\Models\Section;
use App\Models\Shift;
use App\Models\Teacher;
use App\Models\User;
use Carbon\CarbonPeriod;
use Faker\Factory as FakerFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ComprehensiveDemoSeeder extends Seeder
{
    public function run(): void
    {
        $faker = FakerFactory::create('ru_RU');
        $today = now()->startOfDay();
        $monthStart = $today->copy()->subDays(29);

        DB::transaction(function () use ($faker, $monthStart, $today) {
            Attendance::query()->delete();
            Payment::query()->delete();
            Enrollment::query()->delete();
            ActivityLog::query()->delete();
            Child::query()->delete();
            DB::table('section_teacher')->delete();
            Teacher::query()->delete();
            Package::query()->delete();
            Section::query()->delete();
            Room::query()->delete();
            Shift::query()->delete();
            ReceptionSetting::query()->delete();

            $rooms = collect([
                ['name' => 'Творческая мастерская', 'capacity' => 18],
                ['name' => 'Музыкальная студия', 'capacity' => 12],
                ['name' => 'Большой спортивный зал', 'capacity' => 24],
                ['name' => 'Игровая комната', 'capacity' => 16],
            ])->mapWithKeys(function (array $data) {
                $room = Room::create($data);
                return [$room->name => $room];
            });

            $sectionsConfig = [
                [
                    'name' => 'Творческое направление',
                    'room' => 'Творческая мастерская',
                    'schedule_type' => 'weekly',
                    'weekdays' => [1, 3, 5],
                    'children' => [
                        ['name' => 'Акварель и рисунок', 'weekdays' => [1, 3], 'packages' => [
                            ['name' => '8 занятий', 'billing_type' => 'visits', 'visits_count' => 8, 'price' => 24000],
                            ['name' => 'Абонемент на месяц', 'billing_type' => 'period', 'days' => 30, 'price' => 36000],
                        ]],
                        ['name' => 'Лепка и скульптура', 'weekdays' => [2, 4], 'packages' => [
                            ['name' => '10 занятий', 'billing_type' => 'visits', 'visits_count' => 10, 'price' => 28000],
                            ['name' => 'Свободное творчество', 'billing_type' => 'period', 'days' => 45, 'price' => 42000],
                        ]],
                    ],
                ],
                [
                    'name' => 'Музыкальная школа',
                    'room' => 'Музыкальная студия',
                    'schedule_type' => 'weekly',
                    'weekdays' => [2, 4, 6],
                    'children' => [
                        ['name' => 'Фортепиано', 'weekdays' => [2, 5], 'packages' => [
                            ['name' => '6 уроков', 'billing_type' => 'visits', 'visits_count' => 6, 'price' => 30000],
                            ['name' => 'Интенсив 30 дней', 'billing_type' => 'period', 'days' => 30, 'price' => 45000],
                        ]],
                        ['name' => 'Эстрадный вокал', 'weekdays' => [3, 6], 'packages' => [
                            ['name' => '8 репетиций', 'billing_type' => 'visits', 'visits_count' => 8, 'price' => 32000],
                            ['name' => 'Голос на месяц', 'billing_type' => 'period', 'days' => 30, 'price' => 40000],
                        ]],
                    ],
                ],
                [
                    'name' => 'Спортивные программы',
                    'room' => 'Большой спортивный зал',
                    'schedule_type' => 'monthly',
                    'month_days' => [1, 5, 10, 15, 20, 25],
                    'children' => [
                        ['name' => 'Гимнастика', 'weekdays' => [1, 4], 'packages' => [
                            ['name' => '12 посещений', 'billing_type' => 'visits', 'visits_count' => 12, 'price' => 36000],
                            ['name' => 'Фитнес месяц', 'billing_type' => 'period', 'days' => 30, 'price' => 48000],
                        ]],
                        ['name' => 'Командные игры', 'weekdays' => [2, 5], 'packages' => [
                            ['name' => '10 игр', 'billing_type' => 'visits', 'visits_count' => 10, 'price' => 30000],
                            ['name' => 'Игровой сезон', 'billing_type' => 'period', 'days' => 60, 'price' => 55000],
                        ]],
                    ],
                ],
                [
                    'name' => 'Развитие и обучение',
                    'room' => 'Игровая комната',
                    'schedule_type' => 'weekly',
                    'weekdays' => [1, 2, 3, 4, 5],
                    'children' => [
                        ['name' => 'Раннее развитие', 'weekdays' => [1, 3, 5], 'packages' => [
                            ['name' => '15 занятий', 'billing_type' => 'visits', 'visits_count' => 15, 'price' => 42000],
                            ['name' => 'Интенсив 45 дней', 'billing_type' => 'period', 'days' => 45, 'price' => 52000],
                        ]],
                        ['name' => 'Робототехника', 'weekdays' => [2, 4], 'packages' => [
                            ['name' => '8 сборок', 'billing_type' => 'visits', 'visits_count' => 8, 'price' => 38000],
                            ['name' => 'Техно-месяц', 'billing_type' => 'period', 'days' => 30, 'price' => 50000],
                        ]],
                    ],
                ],
            ];

            $leafSections = collect();
            foreach ($sectionsConfig as $config) {
                $root = Section::create([
                    'name' => $config['name'],
                    'room_id' => $rooms[$config['room']]->id,
                    'schedule_type' => $config['schedule_type'],
                    'weekdays' => $config['weekdays'] ?? null,
                    'month_days' => $config['month_days'] ?? null,
                ]);

                foreach ($config['children'] as $childSection) {
                    $section = Section::create([
                        'name' => $childSection['name'],
                        'parent_id' => $root->id,
                        'room_id' => $rooms[$config['room']]->id,
                        'schedule_type' => 'weekly',
                        'weekdays' => $childSection['weekdays'],
                        'is_active' => true,
                    ]);
                    $leafSections->push([$section, $childSection['packages']]);
                }
            }

            $packages = collect();
            foreach ($leafSections as [$section, $packageConfigs]) {
                foreach ($packageConfigs as $packageData) {
                    $package = Package::create([
                        'section_id' => $section->id,
                        'name' => $packageData['name'],
                        'billing_type' => $packageData['billing_type'],
                        'visits_count' => $packageData['visits_count'] ?? null,
                        'days' => $packageData['days'] ?? null,
                        'price' => $packageData['price'],
                        'is_active' => true,
                        'description' => null,
                    ]);
                    $packages->push($package);
                }
            }

            $teacherNames = [
                'Айгерим Садыкова',
                'Даурен Нуркенов',
                'Малика Абдуллаева',
                'Рустам Исмаилов',
                'София Гончаренко',
                'Александр Плотников',
                'Жанна Ержанова',
            ];
            $teachers = collect();
            foreach ($teacherNames as $name) {
                $teachers->push(Teacher::create([
                    'full_name' => $name,
                    'phone' => $faker->phoneNumber(),
                    'salary' => $faker->numberBetween(180000, 280000),
                    'notes' => $faker->sentence(8),
                ]));
            }

            foreach ($leafSections as [$section]) {
                $section->teachers()->syncWithoutDetaching($teachers->random(rand(1, 3))->pluck('id'));
            }

            $receptionists = collect();
            $receptionistProfiles = [
                ['name' => 'Асель Сапарова', 'email' => 'asap@kidsclub.kz'],
                ['name' => 'Камила Омарова', 'email' => 'komarova@kidsclub.kz'],
                ['name' => 'Иван Петров', 'email' => 'ipetrov@kidsclub.kz'],
            ];
            foreach ($receptionistProfiles as $profile) {
                $user = User::factory()->create([
                    'name' => $profile['name'],
                    'email' => $profile['email'],
                ]);
                $user->forceFill(['phone' => $faker->numerify('+7 (7##) ###-##-##')])->save();
                $user->assignRole(User::ROLE_RECEPTIONIST);
                ReceptionSetting::create(array_merge(
                    ['user_id' => $user->id],
                    ReceptionSetting::defaults()
                ));
                $receptionists->push($user);
            }

            $period = new CarbonPeriod($monthStart, $today);
            $dayIndex = 0;
            foreach ($period as $date) {
                $shiftOwner = $receptionists[$dayIndex % $receptionists->count()];
                $scheduledStart = Carbon::parse($date)->setTimeFromTimeString('09:00:00');
                $scheduledEnd = Carbon::parse($date)->setTimeFromTimeString('18:00:00');
                $startedAt = (clone $scheduledStart)->addMinutes(rand(-10, 15));
                $endedAt = (clone $scheduledEnd)->addMinutes(rand(-20, 25));
                if ($endedAt->lessThan($startedAt)) {
                    $endedAt = (clone $scheduledEnd);
                }
                $duration = max($startedAt->diffInMinutes($endedAt, false), 0);
                $closedAutomatically = rand(0, 4) === 0;

                $shift = Shift::create([
                    'user_id' => $shiftOwner->id,
                    'started_at' => $startedAt,
                    'scheduled_start_at' => $scheduledStart,
                    'scheduled_end_at' => $scheduledEnd,
                    'ended_at' => $endedAt,
                    'duration_min' => $duration,
                    'auto_close_enabled' => true,
                    'closed_automatically' => $closedAutomatically,
                ]);

                ActivityLog::create([
                    'user_id' => $shiftOwner->id,
                    'action' => $closedAutomatically ? 'shift.autoclosed' : 'shift.closed',
                    'model' => Shift::class,
                    'model_id' => $shift->id,
                    'payload' => [
                        'started_at' => $startedAt->toIso8601String(),
                        'ended_at' => $endedAt->toIso8601String(),
                        'duration_min' => $duration,
                    ],
                ]);

                $dayIndex++;
            }

            $children = collect();
            for ($i = 0; $i < 100; $i++) {
                $isActive = $i < 85;
                $child = Child::create([
                    'first_name' => $faker->firstName(),
                    'last_name' => $faker->lastName(),
                    'patronymic' => $faker->boolean(70) ? $faker->middleName() : null,
                    'dob' => $faker->dateTimeBetween('-12 years', '-4 years'),
                    'child_phone' => $faker->boolean(50) ? $faker->phoneNumber() : null,
                    'parent_phone' => $faker->numerify('+7 (7##) ###-##-##'),
                    'parent2_phone' => $faker->boolean(40) ? $faker->numerify('+7 (7##) ###-##-##') : null,
                    'is_active' => $isActive,
                    'notes' => $faker->boolean(60) ? $faker->sentence(10) : null,
                ]);
                $children->push($child);

                if (! $isActive) {
                    ActivityLog::create([
                        'user_id' => $receptionists->random()->id,
                        'action' => 'child.deactivated',
                        'model' => Child::class,
                        'model_id' => $child->id,
                        'payload' => ['reason' => $faker->randomElement(['Переезд семьи', 'Длительная болезнь', 'Выбор другой секции'])],
                    ]);
                }
            }

            $enrollmentStates = [];
            foreach ($children as $child) {
                $enrollmentCount = $child->is_active ? rand(1, 2) : rand(0, 1);
                if ($enrollmentCount === 0) {
                    continue;
                }

                for ($j = 0; $j < $enrollmentCount; $j++) {
                    /** @var Package $package */
                    $package = $packages->random();
                    $manager = $receptionists->random();

                    $startDate = Carbon::parse($faker->dateTimeBetween($monthStart->copy()->subDays(10), $today->copy()->subDays(2)));
                    $expiresAt = null;
                    if ($package->billing_type === 'period' && $package->days) {
                        $expiresAt = $startDate->copy()->addDays($package->days);
                    } elseif ($package->billing_type === 'visits') {
                        $expiresAt = $startDate->copy()->addDays(rand(30, 70));
                    }

                    $enrollment = Enrollment::create([
                        'child_id' => $child->id,
                        'section_id' => $package->section_id,
                        'package_id' => $package->id,
                        'started_at' => $startDate,
                        'expires_at' => $expiresAt,
                        'visits_left' => $package->billing_type === 'visits' ? $package->visits_count : null,
                        'price' => $package->price,
                        'total_paid' => 0,
                        'status' => 'pending',
                    ]);

                    ActivityLog::create([
                        'user_id' => $manager->id,
                        'action' => 'enrollment.created',
                        'model' => Enrollment::class,
                        'model_id' => $enrollment->id,
                        'payload' => [
                            'child_id' => $child->id,
                            'package' => $package->name,
                            'started_at' => $startDate->toDateString(),
                        ],
                    ]);

                    $paymentsTotal = 0;
                    $paymentIterations = rand(0, 3);
                    for ($p = 0; $p < $paymentIterations; $p++) {
                        $remaining = $enrollment->price - $paymentsTotal;
                        if ($remaining <= 0) {
                            break;
                        }
                        $lastPayment = $p === $paymentIterations - 1;
                        $amount = $lastPayment || $remaining < 5000
                            ? $remaining
                            : min($remaining, rand(5000, (int) max($remaining * 0.7, 6000)));

                        $paidAt = Carbon::parse($faker->dateTimeBetween($startDate, $today));
                        $cashier = $receptionists->random();
                        $payment = Payment::create([
                            'enrollment_id' => $enrollment->id,
                            'child_id' => $child->id,
                            'amount' => $amount,
                            'paid_at' => $paidAt,
                            'method' => $faker->randomElement(['Наличные', 'Касса 24', 'Перевод']),
                            'comment' => $faker->boolean(30) ? $faker->sentence() : null,
                            'user_id' => $cashier->id,
                        ]);
                        $paymentsTotal += $amount;

                        ActivityLog::create([
                            'user_id' => $cashier->id,
                            'action' => 'payment.created',
                            'model' => Payment::class,
                            'model_id' => $payment->id,
                            'payload' => [
                                'amount' => $amount,
                                'paid_at' => $paidAt->toIso8601String(),
                                'method' => $payment->method,
                            ],
                        ]);
                    }

                    $enrollment->forceFill([
                        'total_paid' => $paymentsTotal,
                    ])->save();

                    $enrollmentStates[$enrollment->id] = [
                        'enrollment' => $enrollment->fresh(),
                        'package' => $package,
                        'visits_limit' => $package->billing_type === 'visits' ? $package->visits_count : null,
                        'visits_used' => 0,
                    ];
                }
            }

            $attendancesByDay = rand(12, 20);
            foreach (new CarbonPeriod($monthStart, $today) as $date) {
                $day = Carbon::parse($date)->startOfDay();
                $available = collect($enrollmentStates)->filter(function ($state) use ($day) {
                    /** @var Enrollment $enrollment */
                    $enrollment = $state['enrollment'];
                    return $enrollment->started_at->lte($day)
                        && (is_null($enrollment->expires_at) || $enrollment->expires_at->gte($day));
                });

                if ($available->isEmpty()) {
                    continue;
                }

                $take = min($available->count(), rand(10, $attendancesByDay));
                $selected = $available->shuffle()->take($take);
                $createdPairs = [];

                foreach ($selected as $state) {
                    /** @var Enrollment $enrollment */
                    $enrollment = $state['enrollment'];
                    $package = $state['package'];
                    $child = $enrollment->child;
                    $section = $enrollment->section;

                    if ($package->billing_type === 'visits'
                        && $state['visits_limit'] !== null
                        && $state['visits_used'] >= $state['visits_limit']) {
                        continue;
                    }

                    $pairKey = $child->id.'-'.$section->id;
                    if (isset($createdPairs[$pairKey])) {
                        continue;
                    }

                    $attendedAt = $day->copy()->setTime(rand(9, 19), rand(0, 59));
                    $marker = $receptionists->random();

                    $attendance = Attendance::create([
                        'child_id' => $child->id,
                        'section_id' => $section->id,
                        'enrollment_id' => $enrollment->id,
                        'room_id' => $section->room_id,
                        'attended_on' => $day->toDateString(),
                        'attended_at' => $attendedAt,
                        'marked_by' => $marker->id,
                    ]);

                    ActivityLog::create([
                        'user_id' => $marker->id,
                        'action' => 'attendance.marked',
                        'model' => Attendance::class,
                        'model_id' => $attendance->id,
                        'payload' => [
                            'child_id' => $child->id,
                            'section' => $section->name,
                            'attended_on' => $day->toDateString(),
                        ],
                    ]);

                    if ($package->billing_type === 'visits' && $state['visits_limit'] !== null) {
                        $enrollmentStates[$enrollment->id]['visits_used']++;
                    }

                    $createdPairs[$pairKey] = true;
                }
            }

            foreach ($enrollmentStates as $id => $state) {
                /** @var Enrollment $enrollment */
                $enrollment = $state['enrollment']->fresh();
                if ($state['visits_limit'] !== null) {
                    $enrollment->visits_left = max($state['visits_limit'] - $state['visits_used'], 0);
                }
                $enrollment->save();
                $enrollment->refreshStatus();
            }

            $this->command?->info(sprintf(
                "Создано: %d детей, %d секций, %d пакетов, %d посещений, %d оплат, %d смен",
                $children->count(),
                Section::count(),
                $packages->count(),
                Attendance::count(),
                Payment::count(),
                Shift::count()
            ));
        });
    }
}
