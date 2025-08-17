<?php

namespace App\Constant;

class FlashMessages
{
    // Booking messages
    public const VISIT_BOOKED_SUCCESS = 'Wizyta została pomyślnie zarezerwowana.';
    public const VISIT_UPDATED_SUCCESS = 'Wizyta została pomyślnie zaktualizowana.';
    public const VISIT_CREATED_SUCCESS = 'Wizyta została pomyślnie utworzona.';
    public const VISIT_DELETED_SUCCESS = 'Wizyta została pomyślnie usunięta.';
    public const VISIT_LOGIN_REQUIRED = 'Musisz być zalogowany, aby zarezerwować wizytę.';
    public const VISIT_EDIT_LOGIN_REQUIRED = 'Musisz być zalogowany, aby edytować wizytę.';
    
    // Opinion messages
    public const OPINION_ADDED_SUCCESS = 'Opinia została dodana pomyślnie.';
    public const OPINION_UPDATED_SUCCESS = 'Opinia została zaktualizowana pomyślnie.';
    public const OPINION_DELETED_SUCCESS = 'Opinia została usunięta pomyślnie.';
    
    // Error messages
    public const BOOKING_NOT_FOUND = 'Rezerwacja nie została znaleziona.';
    public const OPINION_NOT_FOUND = 'Opinia nie została znaleziona.';
    public const OPINION_NOT_ALLOWED_EDIT = 'Nie masz uprawnień do edycji tej opinii.';
    public const OPINION_NOT_ALLOWED_DELETE = 'Nie masz uprawnień do usunięcia tej opinii.';
    public const INVALID_CSRF_TOKEN = 'Nieprawidłowy token CSRF.';
    public const INVALID_TOKEN = 'Nieprawidłowy token';
    public const INTERNAL_SERVER_ERROR = 'Błąd wewnętrzny serwera';
    
    // API error messages
    public const MISSING_PARAMETERS_EMPLOYEE_DATE = 'Brakuje parametrów zapytania (employee, date).';
    public const MISSING_PARAMETERS_OFFER_EMPLOYEE_DATE = 'Brakuje parametrów zapytania (offer, employee, date).';
    public const SERVICE_NOT_FOUND = 'Usługa nie została znaleziona.';
    public const ERROR_FETCHING_AVAILABLE_TIMES = 'Wystąpił błąd podczas pobierania dostępnych terminów: ';
    public const ERROR_OCCURRED = 'Wystąpił błąd: ';
    
    // English equivalents for testing
    public const VISIT_CREATED_SUCCESS_EN = 'The visit has been successfully created.';
    public const VISIT_DELETED_SUCCESS_EN = 'The visit has been successfully deleted.';
    public const SERVICE_NOT_FOUND_EN = 'Service not found.';
    public const INVALID_TOKEN_EN = 'Invalid token';
    public const MISSING_PARAMETERS_OFFER_EMPLOYEE_DATE_EN = 'Missing query parameters (offer, employee, date).';
    public const MISSING_PARAMETERS_EMPLOYEE_DATE_EN = 'Missing query parameters (employee, date).';
    public const BOOKING_NOT_FOUND_EN = 'Booking not found.';
    public const OPINION_NOT_FOUND_EN = 'Opinion not found.';
    public const OPINION_NOT_ALLOWED_EDIT_EN = 'You are not allowed to edit this opinion.';
    public const OPINION_NOT_ALLOWED_DELETE_EN = 'You are not allowed to delete this opinion.';
    public const INVALID_CSRF_TOKEN_EN = 'Invalid CSRF token.';
    public const AN_ERROR_OCCURRED_EN = 'An error occurred.';
    public const EMPLOYEE_NOT_FOUND_EN = 'Employee not found.';
    public const VISIT_NOT_FOUND_EN = 'Visit not found.';
}