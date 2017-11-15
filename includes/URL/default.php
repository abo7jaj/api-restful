<?php

class default_method implements Method
{

    // LIST OF METHOD AS FUNCTION
    function POST(RestUtils &$api)
    {
        if (EDIT_MODE) {
            $api->metier->adjust_create_table($api->program_name);
        }
        if (!$api->db->table_exists($api->program_name)) { // check if there is a table
            $api->sendResponse(404);
        }
        $fields    = $api->metier->get_fields_from_current_table();
        $selection = $api->__path[$api->program_name];
        if ($selection != 0) {// ?as a value == 0
            $api->sendResponse(405);
        }
        $datas  = array('id' => $selection);
        $entrys = $this->return_obj->getRequestVars(); // retrieve user entrys
        foreach ($fields as $field) { // inject datas in column 
            if (isset($entrys[$field])) {
                $datas[$field] = $api->db->sanitize($entrys[$field]);
            }
        }
        $datas['owner_type'] = 'accounts'; // set who is the owner of this data
        $datas['id_owner']   = $api->auth->id; // the auth key corresponding id

        $result = $api->db->insert($api->program_name, $datas);

        if ($result !== FALSE) {
            $api->__path[$api->program_name] = $result;
            self::GET($api);
        } else {
            $api->sendResponse(500);
        }
    }

    function GET(RestUtils &$api, $previousTime = 0)
    {   
        $api->metier->cache(3600*24); // return cache if available, 1 day
        // store beginning time
        $timestart = microtime(true);
        // get arguments passed in url, filter, limit, order, etc
        $filters   = $api->return_obj->getRequestVars();
        if (!$api->db->table_exists($api->program_name)) { // check if there is a table
            $api->sendResponse(404);
        }

        $selection = $api->__path[$api->program_name]; // ?as a value != 0
        $fields    = $api->metier->get_fields_from_current_table();
        if (in_array('id_row', $fields)) {
            $main_id = 'id_row';
        } else {
            $main_id = 'id';
        }
        $where = $order = " ";
        if ($selection != 0) {
            $where .= "WHERE $main_id = '".$api->db->sanitize($selection)."' ";
        } elseif (isset($filters['filter'])) {
            $search = array(); // list of search
            foreach ($filters['filter'] as $key => $value) {
                if (in_array($key, $fields)) {
                    $search[] = "LOWER(`".$api->db->sanitize($key)."`) LIKE LOWER('%".$api->db->sanitize($value)."%')";
                }
            }
            // if we received a non associative array, we will search in all fields
            if (count($search) == 0) {
                foreach ($fields as $column) {
                    $search[] = "LOWER(`$column`) LIKE LOWER('%".$api->db->sanitize($filters['filter'])."%')";
                }
            }
            $where .= "WHERE ".implode(' OR ', $search);
        } else {
            $where .= "WHERE 1 ";
        }
        if (isset($filters['order_by']) && in_array($filters['order_by'], $fields)) {
            $order .= " ORDER BY `".$api->db->sanitize($filters['order_by'])."` ";
        } else {
            $order = " ORDER BY `$main_id` ";
        }
        if (isset($filters['order'])) {
            $order .= $api->db->sanitize($filters['order'])." ";
        } else {
            $order .= "ASC ";
        }
        if ($selection == 0 && isset($filters['limit'])) {
            $limit = " LIMIT ".$api->db->sanitize($filters['limit'])." ";
        } else {
            $limit = " ";
        }
        //    $api->sendResponse(200, $filters);
        $row_result = $api->db->query('SELECT '.DEFAULT_FIELDS.', `'.implode('`, `', $fields).'` FROM '.$api->program_name.$where.$order.$limit);
        $output     = array();
        if ($row_result !== false) {
            while ($row = $api->db->fetch($row_result)) {
                $output[] = array("type" => $api->program_name) + $row;
            }

            $row_count   = $api->db->count($api->program_name, $where, 'count(*)');
            $row_overall = $row_count;
            if ($selection == 0) {
                // total count
                if (isset($filters['limit'])) {
                    if (strpos($filters['limit'], ',') === false) {
                        $row_count = min($row_count, (int) $filters['limit']);
                    } else {
                        list($offset, $limit_count) = explode(',', $filters['limit']);
                        $offset      = trim($offset);
                        $limit_count = trim($limit_count);
                        $row_count   = min(max($row_count - $offset, 0), $limit_count);
                    }
                }
            }
        } else {
            $row_count   = $row_overall = 0;
        }
        // time
        $timeend        = microtime(true);
        $page_load_time = number_format($timeend - $timestart, 3);
        $api->sendResponse(200,
            array("data" => $output, "total" => (int) $row_count, "total_overall" => (int) $row_overall, "execution_time" => $page_load_time
            + $previousTime));
    }

    function PUT(RestUtils &$api)
    {
        // store beginning time
        $timestart = microtime(true);
        if (!$api->db->table_exists($api->program_name)) { // check if there is a table
            $api->sendResponse(404);
        }
        $fields = $api->metier->get_fields_from_current_table();
        if (in_array('id_row', $fields)) {
            $main_id = 'id_row';
        } else {
            $main_id = 'id';
        }
        $selection = $api->__path[$api->program_name];
        if ($selection == 0) {// ?as a value != 0
            $api->sendResponse(405);
        }
        $datas  = array($main_id => $selection);
        $entrys = $this->return_obj->getRequestVars(); // retrieve user entrys
        foreach ($fields as $field) { // inject datas in column 
            if (isset($entrys[$field])) {
                $datas[$field] = $api->db->sanitize($entrys[$field]);
            }
        }
        $result = $api->db->update_by_id($api->program_name, $datas, $main_id);
        if ($result === 1) {
            // time
            $timeend = microtime(true);
            self::GET($api, $timeend - $timestart);
        } elseif ($result === 0) {
            $api->sendResponse(304);
        } else {
            $api->sendResponse(500);
        }
    }

    function DELETE(RestUtils &$api)
    {
        if (!$api->db->table_exists($api->program_name)) { // check if there is a table
            $api->sendResponse(404);
        }
        $fields = $api->metier->get_fields_from_current_table();
        if (in_array('id_row', $fields)) {
            $main_id = 'id_row';
        } else {
            $main_id = 'id';
        }
        $selection = array($main_id => $api->__path[$api->program_name]); // ?as a value != 0
        if ($selection != 0) {
            $api->db->delete($api->program_name, $selection);
            $api->sendResponse(204);
        } else {
            $api->sendResponse(304);
        }
    }
}