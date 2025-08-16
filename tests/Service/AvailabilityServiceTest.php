<?php

namespace App\Tests\Service;

use App\Entity\Booking;
use App\Entity\Employee;
use App\Entity\Offer;
use App\Entity\OpeningHour;
use App\Entity\Service;
use App\Entity\User;
use App\Repository\BookingRepository;
use App\Repository\OpeningHourRepository;
use App\Service\AvailabilityService;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

class AvailabilityServiceTest extends TestCase
{
    private $openingHourRepository;
    private $bookingRepository;
    private $security;
    private $availabilityService;

    protected function setUp(): void
    {
        $this->openingHourRepository = $this->createMock(OpeningHourRepository::class);
        $this->bookingRepository = $this->createMock(BookingRepository::class);
        $this->security = $this->createMock(Security::class);

        $this->availabilityService = new AvailabilityService(
            $this->openingHourRepository,
            $this->bookingRepository,
            $this->security
        );
    }

    public function testGetAvailableSlotsWhenServiceClosed()
    {
        $service = $this->createMock(Service::class);
        $offer = $this->createMock(Offer::class);
        $employee = $this->createMock(Employee::class);
        $date = new \DateTime('2023-06-01');

        $this->openingHourRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with([
                'service' => $service,
                'dayOfWeek' => 'thursday'
            ])
            ->willReturn(null);

        $result = $this->availabilityService->getAvailableSlots($service, $offer, $employee, $date);

        $this->assertEmpty($result, 'Should return an empty array when the service is closed');
    }

    public function testGetAvailableSlotsWhenUserNotLoggedIn()
    {
        $service = $this->createMock(Service::class);
        $offer = $this->createMock(Offer::class);
        $employee = $this->createMock(Employee::class);
        $date = new \DateTime('2023-06-01');
        $openingHour = $this->createMock(OpeningHour::class);
        $openingHour->method('isClosed')->willReturn(false);

        $this->openingHourRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn($openingHour);

        $this->security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn(null);

        $result = $this->availabilityService->getAvailableSlots($service, $offer, $employee, $date);

        $this->assertEmpty($result, 'Should return an empty array when the user is not logged in');
    }

    public function testGetAvailableSlotsWithNoBookings()
    {
        $service = $this->createMock(Service::class);
        $offer = $this->createMock(Offer::class);
        $employee = $this->createMock(Employee::class);
        $user = $this->createMock(User::class);
        $date = new \DateTime('2023-06-01 00:00:00');
        
        $openingHour = $this->createMock(OpeningHour::class);
        $openingHour->method('isClosed')->willReturn(false);
        
        $openingTime = new \DateTime('09:00');
        $closingTime = new \DateTime('17:00');
        $openingHour->method('getOpeningTime')->willReturn($openingTime);
        $openingHour->method('getClosingTime')->willReturn($closingTime);
        
        $offer->method('getDuration')->willReturn(60);

        $this->openingHourRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn($openingHour);

        $this->security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->bookingRepository
            ->expects($this->once())
            ->method('findConflictingBookings')
            ->willReturn([]);

        $this->bookingRepository
            ->expects($this->once())
            ->method('findUserBookings')
            ->willReturn([]);

        $result = $this->availabilityService->getAvailableSlots($service, $offer, $employee, $date);

        $this->assertCount(8, $result, 'Should return 8 available slots (from 9:00 to 16:00, every hour)');
        $this->assertEquals('09:00:00', $result[0]->format('H:i:s'), 'First slot should be at 9:00');
        $this->assertEquals('16:00:00', $result[7]->format('H:i:s'), 'Last slot should be at 16:00');
    }

    public function testGetAvailableSlotsWithEmployeeBookings()
    {
        $service = $this->createMock(Service::class);
        $offer = $this->createMock(Offer::class);
        $employee = $this->createMock(Employee::class);
        $user = $this->createMock(User::class);
        $date = new \DateTime('2023-06-01 00:00:00');
        
        $openingHour = $this->createMock(OpeningHour::class);
        $openingHour->method('isClosed')->willReturn(false);
        
        $openingTime = new \DateTime('09:00');
        $closingTime = new \DateTime('17:00');
        $openingHour->method('getOpeningTime')->willReturn($openingTime);
        $openingHour->method('getClosingTime')->willReturn($closingTime);
        
        $offer->method('getDuration')->willReturn(60);

        $employeeBooking = $this->createMock(Booking::class);
        $startTime = new \DateTime('2023-06-01 11:00:00');
        $endTime = new \DateTime('2023-06-01 12:00:00');
        $employeeBooking->method('getStartTime')->willReturn($startTime);
        $employeeBooking->method('getEndTime')->willReturn($endTime);

        $this->openingHourRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn($openingHour);

        $this->security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->bookingRepository
            ->expects($this->once())
            ->method('findConflictingBookings')
            ->willReturn([$employeeBooking]);

        $this->bookingRepository
            ->expects($this->once())
            ->method('findUserBookings')
            ->willReturn([]);

        $result = $this->availabilityService->getAvailableSlots($service, $offer, $employee, $date);

        $this->assertCount(7, $result, 'Should return 7 available slots (without 11:00)');
        
        $slotTimes = array_map(function($slot) {
            return $slot->format('H:i:s');
        }, $result);
        
        $this->assertContains('09:00:00', $slotTimes);
        $this->assertContains('10:00:00', $slotTimes);
        $this->assertContains('12:00:00', $slotTimes);
        $this->assertContains('13:00:00', $slotTimes);
        $this->assertContains('14:00:00', $slotTimes);
        $this->assertContains('15:00:00', $slotTimes);
        $this->assertContains('16:00:00', $slotTimes);
        $this->assertNotContains('11:00:00', $slotTimes);
    }

    public function testGetAvailableSlotsWithUserBookings()
    {
        $service = $this->createMock(Service::class);
        $offer = $this->createMock(Offer::class);
        $employee = $this->createMock(Employee::class);
        $user = $this->createMock(User::class);
        $date = new \DateTime('2023-06-01 00:00:00');
        
        $openingHour = $this->createMock(OpeningHour::class);
        $openingHour->method('isClosed')->willReturn(false);
        
        $openingTime = new \DateTime('09:00');
        $closingTime = new \DateTime('17:00');
        $openingHour->method('getOpeningTime')->willReturn($openingTime);
        $openingHour->method('getClosingTime')->willReturn($closingTime);
        
        $offer->method('getDuration')->willReturn(60);

        $userBooking = $this->createMock(Booking::class);
        $startTime = new \DateTime('2023-06-01 14:00:00');
        $endTime = new \DateTime('2023-06-01 15:00:00');
        $userBooking->method('getStartTime')->willReturn($startTime);
        $userBooking->method('getEndTime')->willReturn($endTime);

        $this->openingHourRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn($openingHour);

        $this->security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->bookingRepository
            ->expects($this->once())
            ->method('findConflictingBookings')
            ->willReturn([]);

        $this->bookingRepository
            ->expects($this->once())
            ->method('findUserBookings')
            ->willReturn([$userBooking]);

        $result = $this->availabilityService->getAvailableSlots($service, $offer, $employee, $date);

        $this->assertCount(7, $result, 'Should return 7 available slots (without 14:00)');
        
        $slotTimes = array_map(function($slot) {
            return $slot->format('H:i:s');
        }, $result);
        
        $this->assertContains('09:00:00', $slotTimes);
        $this->assertContains('10:00:00', $slotTimes);
        $this->assertContains('11:00:00', $slotTimes);
        $this->assertContains('12:00:00', $slotTimes);
        $this->assertContains('13:00:00', $slotTimes);
        $this->assertContains('15:00:00', $slotTimes);
        $this->assertContains('16:00:00', $slotTimes);
        $this->assertNotContains('14:00:00', $slotTimes);
    }

    public function testGetAvailableSlotsWithMultipleBookings()
    {
        $service = $this->createMock(Service::class);
        $offer = $this->createMock(Offer::class);
        $employee = $this->createMock(Employee::class);
        $user = $this->createMock(User::class);
        $date = new \DateTime('2023-06-01 00:00:00');
        
        $openingHour = $this->createMock(OpeningHour::class);
        $openingHour->method('isClosed')->willReturn(false);
        
        $openingTime = new \DateTime('09:00');
        $closingTime = new \DateTime('17:00');
        $openingHour->method('getOpeningTime')->willReturn($openingTime);
        $openingHour->method('getClosingTime')->willReturn($closingTime);
        
        $offer->method('getDuration')->willReturn(60);

        $employeeBooking = $this->createMock(Booking::class);
        $startTime = new \DateTime('2023-06-01 10:00:00');
        $endTime = new \DateTime('2023-06-01 11:00:00');
        $employeeBooking->method('getStartTime')->willReturn($startTime);
        $employeeBooking->method('getEndTime')->willReturn($endTime);

        $userBooking = $this->createMock(Booking::class);
        $startTime2 = new \DateTime('2023-06-01 13:00:00');
        $endTime2 = new \DateTime('2023-06-01 14:00:00');
        $userBooking->method('getStartTime')->willReturn($startTime2);
        $userBooking->method('getEndTime')->willReturn($endTime2);

        $this->openingHourRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn($openingHour);

        $this->security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->bookingRepository
            ->expects($this->once())
            ->method('findConflictingBookings')
            ->willReturn([$employeeBooking]);

        $this->bookingRepository
            ->expects($this->once())
            ->method('findUserBookings')
            ->willReturn([$userBooking]);

        $result = $this->availabilityService->getAvailableSlots($service, $offer, $employee, $date);

        $this->assertCount(6, $result, 'Should return 6 available slots (without 10:00 and 13:00)');
        
        $slotTimes = array_map(function($slot) {
            return $slot->format('H:i:s');
        }, $result);
        
        $this->assertContains('09:00:00', $slotTimes);
        $this->assertContains('11:00:00', $slotTimes);
        $this->assertContains('12:00:00', $slotTimes);
        $this->assertContains('14:00:00', $slotTimes);
        $this->assertContains('15:00:00', $slotTimes);
        $this->assertContains('16:00:00', $slotTimes);
        $this->assertNotContains('10:00:00', $slotTimes);
        $this->assertNotContains('13:00:00', $slotTimes);
    }

    public function testGetAvailableSlotsWithOverlappingTime()
    {
        $service = $this->createMock(Service::class);
        $offer = $this->createMock(Offer::class);
        $employee = $this->createMock(Employee::class);
        $user = $this->createMock(User::class);
        $date = new \DateTime('2023-06-01 00:00:00');
        
        $openingHour = $this->createMock(OpeningHour::class);
        $openingHour->method('isClosed')->willReturn(false);
        
        $openingTime = new \DateTime('09:00');
        $closingTime = new \DateTime('12:00');
        $openingHour->method('getOpeningTime')->willReturn($openingTime);
        $openingHour->method('getClosingTime')->willReturn($closingTime);
        
        $offer->method('getDuration')->willReturn(60);

        $employeeBooking = $this->createMock(Booking::class);
        $startTime = new \DateTime('2023-06-01 10:30:00');
        $endTime = new \DateTime('2023-06-01 11:30:00');
        $employeeBooking->method('getStartTime')->willReturn($startTime);
        $employeeBooking->method('getEndTime')->willReturn($endTime);

        $this->openingHourRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn($openingHour);

        $this->security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->bookingRepository
            ->expects($this->once())
            ->method('findConflictingBookings')
            ->willReturn([$employeeBooking]);

        $this->bookingRepository
            ->expects($this->once())
            ->method('findUserBookings')
            ->willReturn([]);

        $result = $this->availabilityService->getAvailableSlots($service, $offer, $employee, $date);

        $this->assertCount(1, $result, 'Should only be one slot left (09:00)');
        $this->assertEquals('09:00:00', $result[0]->format('H:i:s'), 'The available slot should be at 9:00');
    }

    public function testGetAvailableSlotsWithShorterDuration()
    {
        $service = $this->createMock(Service::class);
        $offer = $this->createMock(Offer::class);
        $employee = $this->createMock(Employee::class);
        $user = $this->createMock(User::class);
        $date = new \DateTime('2023-06-01 00:00:00');
        
        $openingHour = $this->createMock(OpeningHour::class);
        $openingHour->method('isClosed')->willReturn(false);
        
        $openingTime = new \DateTime('09:00');
        $closingTime = new \DateTime('12:00');
        $openingHour->method('getOpeningTime')->willReturn($openingTime);
        $openingHour->method('getClosingTime')->willReturn($closingTime);
        
        $offer->method('getDuration')->willReturn(30);

        $this->openingHourRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn($openingHour);

        $this->security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->bookingRepository
            ->expects($this->once())
            ->method('findConflictingBookings')
            ->willReturn([]);

        $this->bookingRepository
            ->expects($this->once())
            ->method('findUserBookings')
            ->willReturn([]);

        $result = $this->availabilityService->getAvailableSlots($service, $offer, $employee, $date);

        $this->assertCount(6, $result, 'Should return 6 available slots (from 9:00 to 11:30, every 30 minutes)');
        
        $slotTimes = array_map(function($slot) {
            return $slot->format('H:i:s');
        }, $result);
        
        $this->assertContains('09:00:00', $slotTimes);
        $this->assertContains('09:30:00', $slotTimes);
        $this->assertContains('10:00:00', $slotTimes);
        $this->assertContains('10:30:00', $slotTimes);
        $this->assertContains('11:00:00', $slotTimes);
        $this->assertContains('11:30:00', $slotTimes);
    }
}
