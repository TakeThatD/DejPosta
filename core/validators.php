<?php
function validateDate($date, $format)
{
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) == $date;
}

function validateMail($mail)
{
  if (filter_var($mail, FILTER_VALIDATE_EMAIL)){
    return true;
  } else {
    return false;
  }
}

function validatePhone($phone)
{
  $phone = preg_replace("/[^0-9]/","", $phone);
  if (strlen($phone) == 9){
    return true;
  } else {
    return false;
  }
}
