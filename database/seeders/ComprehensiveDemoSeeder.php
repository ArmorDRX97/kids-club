<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\Child;
use App\Models\Direction;
use App\Models\Enrollment;
use App\Models\Package;
use App\Models\Payment;
use App\Models\ReceptionSetting;
use App\Models\Room;
use App\Models\Section;
use App\Models\SectionSchedule;
use App\Models\Shift;
use App\Models\Teacher;
use App\Models\User;
use Carbon\CarbonPeriod;
use Faker\Factory as FakerFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
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
            SectionSchedule::query()->delete();
            Section::query()->delete();
            Direction::query()->delete();
            Room::query()->delete();
            Shift::query()->delete();
            ReceptionSetting::query()->delete();

            $rooms = collect([
                ['name' => 'Большой творческий зал', 'capacity' => 20, 'number_label' => 'A1'],
                ['name' => 'Студия хореографии', 'capacity' => 14, 'number_label' => 'B3'],
                ['name' => 'Игровая комната', 'capacity' => 18, 'number_label' => 'C2'],
                ['name' => 'Музыкальная мастерская', 'capacity' => 12, 'number_label' => 'D1'],
            ])->mapWithKeys(function (array $data) {
                $room = Room::create($data);
                return [$room->name => $room];
            });

            $directionConfigs = [
                [
                    'name' => 'Творческое развитие',
                    'sections' => [
                        [
                            'name' => 'Акварель и рисунок',
                            'room' => 'Большой творческий зал',
                            'schedules' => [
                                1 => [['09:00', '11:00'], ['11:30', '13:00']],
                                3 => [['10:00', '12:00']],
                            ],
                            'packages' => [
                                ['name' => '8 занятий', 'billing_type' => 'visits', 'visits_count' => 8, 'price' => 26000],
                                ['name' => 'Абонемент на месяц', 'billing_type' => 'period', 'days' => 30, 'price' => 36000],
                            ],
                        ],
                        [
                            'name' => 'Студия лепки',
                            'room' => 'Игровая комната',
                            'schedules' => [
                                2 => [['15:00', '17:00']],
                                4 => [['15:00', '17:00']],
                                6 => [['10:30', '12:30']],
                            ],
                            'packages' => [
                                ['name' => '6 занятий', 'billing_type' => 'visits', 'visits_count' => 6, 'price' => 21000],
                                ['name' => 'Интенсив 14 дней', 'billing_type' => 'period', 'days' => 14, 'price' => 18000],
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'Спорт и движение',
                    'sections' => [
                        [
                            'name' => 'Современные танцы',
                            'room' => 'Студия хореографии',
                            'schedules' => [
                                1 => [['18:00', '19:30']],
                                3 => [['18:00', '19:30']],
                                5 => [['18:00', '19:30']],
                            ],
                            'packages' => [
                                ['name' => '12 тренировок', 'billing_type' => 'visits', 'visits_count' => 12, 'price' => 34000],
                                ['name' => 'Абонемент 45 дней', 'billing_type' => 'period', 'days' => 45, 'price' => 42000],
                            ],
                        ],
                        [
                            'name' => 'Йога для детей',
                            'room' => 'Музыкальная мастерская',
                            'schedules' => [
                                2 => [['09:30', '10:30']],
                                4 => [['09:30', '10:30']],
                                6 => [['11:00', '12:00']],
                            ],
                            'packages' => [
                                ['name' => '8 занятий', 'billing_type' => 'visits', 'visits_count' => 8, 'price' => 24000],
                                ['name' => 'Абонемент на месяц', 'billing_type' => 'period', 'days' => 30, 'price' => 30000],
                            ],
                        ],
                    ],
                ],
            ];

            $directions = collect();
            $sections = collect();
            $packages = collect();

            foreach ($directionConfigs as $directionData) {
                /** @var Direction $direction */
                $direction = Direction::create(['name' => $directionData['name']]);
                $directions->push($direction);

                foreach ($directionData['sections'] as $sectionData) {
                    /** @var Section $section */
                    $section = Section::create([
                        'name' => $sectionData['name'],
                        'direction_id' => $direction->id,
                        'room_id' => $rooms[$sectionData['room']]?->id,
                        'is_active' => true,
                    ]);
                    $sections->push($section);

                    foreach ($sectionData['schedules'] as $weekday => $slots) {
                        foreach ($slots as [$start, $end]) {
                            $section->schedules()->create([
                                'weekday' => $weekday,
                                'starts_at' => $start,
                                'ends_at' => $end,
                            ]);
                        }
                    }

                    foreach ($sectionData['packages'] as $packageData) {
                        $package = Package::create([
                            'section_id' => $section->id,
                            'name' => $packageData['name'],
                            'billing_type' => $packageData['billing_type'],
                            'visits_count' => Arr::get($packageData, 'visits_count'),
                            'days' => Arr::get($packageData, 'days'),
                            'price' => $packageData['price'],
                            'is_active' => true,
                        ]);
                        $packages->push($package);
                    }
                }
            }

            $teacherNames = [
                'Марина Белова',
                'Инна Комарова',
                'Пётр Астахов',
                'Галина Субботина',
                'Кристина Егорова',
                'Лилия Пашкова',
                'Батыр Сейитов',
            ];

            $teachers = collect();
            foreach ($teacherNames as $name) {
                $teachers->push(Teacher::create([
                    'full_name' => $name,
                    'phone' => $faker->numerify('+7 (7##) ###-##-##'),
                    'salary' => $faker->numberBetween(180000, 280000),
                    'notes' => $faker->sentence(8),
                ]));
            }

            foreach ($sections as $section) {
                $section->teachers()->syncWithoutDetaching($teachers->random(rand(1, 3))->pluck('id'));
            }

            $receptionists = collect();
            $receptionistProfiles = [
                ['name' => 'Айжан Сапарова', 'email' => 'sap@kidsclub.kz'],
                ['name' => 'Надежда Комарова', 'email' => 'komarova@kidsclub.kz'],
                ['name' => 'Илья Петров', 'email' => 'ipetrov@kidsclub.kz'],
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

            $admin = User::factory()->create([
                'name' => 'Александр Гордеев',
                'email' => 'admin@kidsclub.kz',
            ]);
            $admin->assignRole(User::ROLE_ADMIN);
            $admin->forceFill(['phone' => '+7 (700) 000-00-00'])->save();

            ReceptionSetting::create(array_merge(
                ['user_id' => $admin->id],
                ReceptionSetting::defaults()
            ));

            $children = collect();
            for ($i = 0; $i < 40; $i++) {
                $child = Child::create([
                    'first_name' => $faker->firstName(),
                    'last_name' => $faker->lastName(),
                    'patronymic' => $faker->boolean(60) ? $faker->firstName() . 'ович' : null,
                    'dob' => $faker->dateTimeBetween('-12 years', '-4 years'),
                    'child_phone' => $faker->boolean(30) ? $faker->numerify('+7 (7##) ###-##-##') : null,
                    'parent_phone' => $faker->numerify('+7 (7##) ###-##-##'),
                    'parent2_phone' => $faker->boolean(40) ? $faker->numerify('+7 (7##) ###-##-##') : null,
                    'notes' => $faker->boolean(30) ? $faker->sentence(6) : null,
                    'is_active' => $faker->boolean(85),
                ]);
                $children->push($child);
            }

            $enrollmentStates = [];
            foreach ($children as $child) {
                $enrollmentCount = $child->is_active ? rand(1, 2) : rand(0, 1);
                if ($enrollmentCount === 0) {
                    continue;
                }

                $desiredCount = $enrollmentCount;
                $assignedSections = [];
                $shuffledPackages = $packages->shuffle();

                foreach ($shuffledPackages as $package) {
                    if (count($assignedSections) >= $desiredCount) {
                        break;
                    }

                    $section = $package->section;
                    if (isset($assignedSections[$section->id])) {
                        continue;
                    }

                    $schedule = $section->schedules()->inRandomOrder()->first();
                    if (! $schedule) {
                        continue;
                    }

                    $assignedSections[$section->id] = true;
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
                        'section_id' => $section->id,
                        'section_schedule_id' => $schedule->id,
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
                            'schedule_id' => $schedule->id,
                            'schedule_label' => $schedule->weekday . ' ' . $schedule->starts_at->format('H:i') . '–' . $schedule->ends_at->format('H:i'),
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
                            'method' => $faker->randomElement(['Наличные', 'Kaspi', 'Безналичный расчёт']),
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
                        'schedule' => $schedule,
                        'visits_limit' => $package->billing_type === 'visits' ? $package->visits_count : null,
                        'visits_used' => 0,
                    ];
                }
            }

            $attendancesPerDay = rand(12, 20);
            foreach (new CarbonPeriod($monthStart, $today) as $date) {
                $day = Carbon::parse($date)->startOfDay();
                $weekday = $day->isoWeekday();

                $available = collect($enrollmentStates)->filter(function ($state) use ($day, $weekday) {
                    /** @var Enrollment $enrollment */
                    $enrollment = $state['enrollment'];
                    $schedule = $state['schedule'];

                    return $schedule && $schedule->weekday === $weekday
                        && $enrollment->started_at->lte($day)
                        && (is_null($enrollment->expires_at) || $enrollment->expires_at->gte($day));
                });

                if ($available->isEmpty()) {
                    continue;
                }

                $take = min($available->count(), rand(10, $attendancesPerDay));
                $selected = $available->shuffle()->take($take);
                $createdPairs = [];

                foreach ($selected as $state) {
                    /** @var Enrollment $enrollment */
                    $enrollment = $state['enrollment'];
                    $package = $state['package'];
                    $schedule = $state['schedule'];
                    $child = $enrollment->child;
                    $section = $enrollment->section;

                    if ($package->billing_type === 'visits'
                        && $state['visits_limit'] !== null
                        && $state['visits_used'] >= $state['visits_limit']) {
                        continue;
                    }

                    $pairKey = $child->id.'-'.$section->id.'-'.$schedule->id;
                    if (isset($createdPairs[$pairKey])) {
                        continue;
                    }

                    $startTime = Carbon::parse($schedule->starts_at);
                    $endTime = Carbon::parse($schedule->ends_at);
                    $attendedAt = $day->copy()->setTimeFromTimeString(
                        $startTime->copy()->addMinutes(rand(0, max(1, $startTime->diffInMinutes($endTime) - 10)))->format('H:i')
                    );

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
                            'schedule_id' => $schedule->id,
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
                "Сгенерировано: %d детей, %d секций, %d пакетов, %d отметок, %d платежей, %d смен",
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
