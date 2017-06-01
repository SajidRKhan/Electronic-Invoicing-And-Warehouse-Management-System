<?php

/*
 * @Author:    Kiril Kirkov
 *  Github:    https://github.com/kirilkirkov
 */
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Employees extends USER_Controller
{

    private $num_rows = 1;
    private $editId;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('SettingsModel');
    }

    public function index($page = 0)
    {
        $data = array();
        $head = array();
        $head['title'] = 'Administration - Settings';
        $rowscount = $this->SettingsModel->countEmployees($_GET);
        $data['employees'] = $this->SettingsModel->getEmployees($this->num_rows, $page);
        $data['linksPagination'] = pagination('user/settings/employees', $rowscount, $this->num_rows, 4);
        $this->render('settings/employees', $head, $data);
        $this->saveHistory('Go to settings employees table page');
    }

    public function addNew($id = 0)
    {
        $data = array();
        $head = array();
        $head['title'] = 'Administration - Settings';
        $this->editId = $id;
        if (isset($_POST['name'])) {
            $_POST['editId'] = $id;
            $this->addEmployee();
        }
        if ($id > 0) {
            $result = $this->SettingsModel->getEmployeeInfo($id);
            if (empty($result)) {
                show_404();
            }
            unset($result['password']);
            $_POST = $result;
        }
        if ($this->session->flashdata('saveData') != null) {
            $_POST = $this->session->flashdata('saveData');
        }
        $data['editId'] = $this->editId;
        $this->render('settings/addEmployee', $head, $data);
        $this->saveHistory('Go to settings employees add page');
    }

    private function addEmployee()
    {
        $isValid = $this->validateEmployee();
        if ($isValid === true) {
            $insertId = $this->SettingsModel->setEmployee($_POST);
            if ($this->editId == 0) {
                $this->setNewEmployeePermissions($insertId);
            }
            $this->saveHistory('Add employee - ' . $_POST['email']);
            redirect(lang_url('user/settings/employees'));
        } else {
            $this->session->set_flashdata('resultAction', $isValid);
            $this->session->set_flashdata('saveData', $_POST);
            if ($this->editId > 0) {
                redirect(lang_url('user/settings/employees/add/' . $this->editId));
            } else {
                redirect(lang_url('user/settings/employees/add'));
            }
        }
    }

    private function setNewEmployeePermissions($employeeId)
    {
        $defaultPermissions = $this->config->item('permissions');
        $this->SettingsModel->setNewEmployeePermissions($employeeId, $defaultPermissions);
    }

    private function validateEmployee()
    {
        $errors = array();
        if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = lang('invalid_email');
        } else {
            $isFree = $this->SettingsModel->checkEmployeeFreeEmail($_POST['email'], $this->editId);
            if ($isFree == false) {
                $errors[] = lang('employee_email_taken');
            }
        }
        if ($this->editId == 0) {
            if (mb_strlen(trim($_POST['password'])) == 0) {
                $errors[] = lang('empty_password');
            }
        }
        if (empty($errors)) {
            return true;
        }
        return $errors;
    }

    public function deleteEmployee($id)
    {
        $this->SettingsModel->deleteEmployee($id);
        redirect(lang_url('user/settings/employees'));
    }

}