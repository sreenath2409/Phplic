<?php

/** DB operation to either add or edit a form, based on $_POST['id'] */
function db_process() {
    $id = $_POST['submit_id'];
    $name = $_POST['name'];
    $label = $_POST['label'];
    // checkboxes are not included in POST when unchecked.
    $is_active = isset($_POST['is_active']) && $_POST['is_active'] === "on" ? 1 : 0;

    // Error check.  On error, stop and return error message.
    switch(false) {
    // $id must be all numbers or the word "new"
    case preg_match("/^\d+$|^new$/", $id):
        return array('msg' => "Invalid server ID \"{$id}\"", 'lvl' => "failure");
    // $name must match port@domain.tld
    case preg_match("/^\d{1,5}@(?:[a-z\d\-]+\.)+[a-z\-]{2,}$/i", $name,):
        return array('msg' => "Server name MUST be in form <code>port@domain.tld</code>", 'lvl' => "failure");
    // $label cannot be blank
    case !empty($label):
        return array('msg' => "Server's label cannot be blank", 'lvl' => "failure");
    }
    // END error check

    if ($id === "new") {
        // Adding a new server
        $sql = "INSERT INTO `servers` (`name`, `label`, `is_active`) VALUES (?, ?, ?)";
        $params = array("ssi", $name, $label, $is_active);
        $op = "added";
    } else {
        // Editing an existing server
        $sql = "UPDATE `servers` SET `name`=?, `label`=?, `is_active`=? WHERE `ID`=?";
        $params = array("ssii", $name, $label, $is_active, $id);
        $op = "updated";
    }

    db_connect($db);
    $query = $db->prepare($sql);
    $query->bind_param(...$params);
    $query->execute();

    if (empty($db->error_list)) {
        $response_msg = array('msg' => "{$name} ({$label}) successfully {$op}.", 'lvl' => "success");
    } else {
        $response_msg = array('msg' => "(${name}) DB Error: {$db->error}.", 'lvl' => "failure");
    }

    $query->close();
    $db->close();
    return $response_msg;
} // END function db_process()

/**
 * Retrieve server details by server ID.
 *
 * @param int $id
 * @return array server's name, label and active status.
 */
function db_server_details_by_getid($id) {
    db_connect($db);
    $server_details = db_get_servers($db, array("name", "label", "is_active"), array($id), "", false);
    $db->close();
    return !empty($server_details) ? $server_details[0] : false;
} // END function db_server_details_by_getid()

function db_delete_server() {
    // validate
    if (ctype_digit($_POST['delete_id'])) {
        $id = $_POST['delete_id'];
    } else {
        return array('msg' => "Validation failed when attempting to remove a server from DB.", 'lvl' => "failure");
    }

    $sql = "DELETE FROM `servers` WHERE `id`=?";
    $params = array("i", intval($id));

    db_connect($db);
    $details = db_get_servers($db, array('name', 'label'), array($id), "", false)[0];
    $name = $details['name'];
    $label = $details['label'];
    $query = $db->prepare($sql);
    $query->bind_param(...$params);
    $query->execute();

    if (empty($db->error_list)) {
        $response = array('msg' => "Successfully deleted ID {$id}: \"{$name}\" ({$label})", 'lvl' => "success");
    } else {
        $response = array('msg' => "ID ${id}: \"${name}\" ({$label}), DB Error: \"{$db->error}\"", 'lvl' => "failure");
    }

    $query->close();
    $db->close();

    return $response;
} // END function db_delete_server()

function db_get_servers_json() {

    $data = array();
    $sql = "SELECT `name`, `label`, `is_active` FROM `servers`;";

    db_connect($db);
    $res = $db->query($sql);

    $row = $res->fetch_assoc();
    while (!is_null($row)) {
        $data[] = $row;
        $row = $res->fetch_assoc();
    }

    $res->free();
    $db->close();

    return json_encode($data);
}

?>
