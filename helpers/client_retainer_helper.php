<?php

defined('BASEPATH') or exit('No direct script access allowed');

function get_monthnames()
{
  return [
    1 => 'January',
    2 => 'February',
    3 => 'March',
    4 => 'April',
    5 => 'May',
    6 => 'June',
    7 => 'July',
    8 => 'August',
    9 => 'September',
    10 => 'October',
    11 => 'November',
    12 => 'December'
  ];
}

function get_fymonths()
{
  return [
    1 => 7,
    2 => 8,
    3 => 9,
    4 => 10,
    5 => 11,
    6 => 12,
    7 => 1,
    8 => 2,
    9 => 3,
    10 => 4,
    11 => 5,
    12 => 6
  ];
}

function get_fyyear($month)
{
  $now = new DateTime();
  if ($now->format('m') > 6) {
    if ($month > 6) {
      return $now->format('Y');
    } else {
      return $now->format('Y') + 1;
    }
  } else {
    if ($month > 6) {
      return $now->format('Y') - 1;
    } else {
      return $now->format('Y');
    }
  }
}

function get_custom_field_id_from_slug($slug)
{
  $CI = &get_instance();
  $CI->db->where('slug', $slug);
  $row = $CI->db->get(db_prefix() . 'customfields')->row();
  return $row->id;
}
