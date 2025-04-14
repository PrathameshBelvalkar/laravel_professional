<?php

use App\Http\Resources\GeneralResponse;
use App\Models\Country;

if (!function_exists('getPhoneCodeByCountryId')) {
    function getPhoneCodeByCountryId($countryId)
    {
        $country = DB::table('countries')
            ->select('phonecode')
            ->where('id', $countryId)
            ->first();
        
        return $country->phonecode ?? null;
    }
}
if (!function_exists('getcountrynamebyid')) {
    function getcountrynamebyid($countryId)
    {
        $country = DB::table('countries')
            ->select('name')
            ->where('id', $countryId)
            ->first();
        
        return $country->name ?? null;
    }
}
if (!function_exists('getstatenamebyid')) {
    function getstatenamebyid($stateId)
    {
        $state = DB::table('states')
            ->select('name')
            ->where('id', $stateId)
            ->first();
            
        return $state->name ?? null;
    }
}
if (!function_exists('getTabledata')) {
    function getTabledata($tblname,$where_column_name,$column_nm)
    {
        $data = DB::table($tblname)
            ->select('*')
            ->where($where_column_name, $column_nm)
            ->first();
            
        return $data ?? null;
    }
}
if (!function_exists('getstatus')) {
    function getstatus($status)
    {
        if($status == '0'){
            $status_nm = 'Pending';
        }elseif($status == '1'){
            $status_nm = 'Approved';
        }elseif($status == '2'){
            $status_nm = 'Rejected';

        }
        return $status_nm ?? null;
    }
}

if (!function_exists('getCategoryOrSubCategory')) {
    function getCategoryOrSubCategory($table, $column_name, $id)
    {
        $result = DB::table($table)
            ->select($column_name)
            ->where('id', $id)
            ->first();

        // Return the column value directly
        return $result ? $result->$column_name : null;
    }
}

if (!function_exists('getadmindetails')) {
    function getadmindetails()
    {
        $data = getTabledata('users','role_id','1');
        return $data->id ?? null;
    }
}
if (!function_exists('getDomainFromEmail')) {
function getDomainFromEmail($email) {
    $parts = explode('@', $email);

    if (count($parts) == 2) {
        return $parts[1]; 
    } else {
        return null; 
    }
}
}


