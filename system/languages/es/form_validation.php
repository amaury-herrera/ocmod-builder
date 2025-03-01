<?php

class FormValidationErrors {
    public static $required = 'El campo %s es obligatorio.';
    public static $if_set = 'El campo %s debe contener un valor.';
    public static $valid_email = 'El campo %s debe contener una dirección de correo electrónico';
    public static $valid_emails = 'El campo %s debe contener todas las direcciones de email válidas.';
    public static $valid_date = 'El campo %s debe contener una fecha válida';
    public static $valid_url = 'El campo %s debe contener una URL válida';
    public static $valid_ip = 'El campo %s debe contener una IP válida';
    public static $valid_ipv4 = 'El campo %s debe contener una IPv4 válida';
    public static $valid_ipv6 = 'El campo %s debe contener una IPv6 válida';
    public static $min_length = 'El campo %s debe tener al menos %s caracteres de longitud.';
    public static $max_length = 'El campo %s no puede exceder los %s caracteres de longitud.';
    public static $exact_length = 'El campo %s debe tener exactamente %s caracteres de longitud.';
    public static $alpha = 'El campo %s debe contener solamente caracteres del alfabeto.';
    public static $alpha_numeric = 'El campo %s debe contener solamente caracteres alfanuméricos.';
    public static $alpha_dash = 'El campo %s debe contener solamente caracteres alfanuméricos, subrayados y /.';
    public static $numeric = 'El campo %s debe contener solamente números.';
    public static $is_numeric = 'El campo %s debe contener solamente caraceres numéricos.';
    public static $integer = 'El campo %s debe contener un número entero.';
    public static $regex_match = 'El campo %s no tiene el formato correcto.';
    public static $matches = 'El campo %s no coincide con el campo %s.';
    public static $is_unique = 'El campo %s debe contener un único valor.';
    public static $is_natural = 'El campo %s debe contener números positivos solamente.';
    public static $is_natural_no_zero = 'El campo %s debe contener un número mayor que cero.';
    public static $decimal = 'El campo %s debe contener un número decimal.';
    public static $less_than = 'El campo %s debe contener un número menor que %s.';
    public static $greater_than = 'El campo %s debe contener un número mayor que %s.';
    public static $in_list = 'El valor del campo %s no está entre los valores permitidos.';
    public static $between = 'El valor del campo %s debe estar comprendido entre %s y %s.';
    public static $only_chars = 'El campo %s contiene caracteres no permitidos.';
    public static $not_contains = 'El campo %s contiene caracteres no permitidos.';
    public static $password_match = 'El valor del campo %s no es válido.';
}