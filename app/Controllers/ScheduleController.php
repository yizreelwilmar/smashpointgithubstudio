<?php
// app/Controllers/ScheduleController.php
// Public Schedule & RSVP Controller

require_once __DIR__ . '/../Models/Session.php';
require_once __DIR__ . '/../Models/Player.php';

class ScheduleController {
    public static function index(): void {
        $upcomingSessions = Session::upcoming(10);
        $pastSessions = Session::all();
        require __DIR__ . '/../../views/pages/schedule.php';
    }
}
