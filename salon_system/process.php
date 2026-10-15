<?php
require_once 'functions.php';

// Handle CREATE
if (isset($_POST['create'])) {
    if (createAppointment($_POST)) {
        $_SESSION['message'] = "Appointment created successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error creating appointment.";
        $_SESSION['message_type'] = "error";
    }
    header("Location: index.php");
    exit();
}

// Handle UPDATE
if (isset($_POST['update'])) {
    if (updateAppointment($_POST['id'], $_POST)) {
        $_SESSION['message'] = "Appointment updated successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error updating appointment.";
        $_SESSION['message_type'] = "error";
    }
    header("Location: index.php");
    exit();
}

// Handle DELETE
if (isset($_GET['delete'])) {
    if (deleteAppointment($_GET['delete'])) {
        $_SESSION['message'] = "Appointment deleted successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error deleting appointment.";
        $_SESSION['message_type'] = "error";
    }
    header("Location: index.php");
    exit();
}
?>

