<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Org extends CI_Controller{

  private $viewDir   = 'org/';
  private $ctrlClass = 'Org/';

  public function __construct()
  {
    parent::__construct();
    $this->load->model('OrgModel');

  }

  function index()
  {
    $this->session->unset_userdata('selectId');
    $this->session->unset_userdata('filterBegDa');
    $this->session->unset_userdata('filterEndDa');
    $begin = $this->input->post('dt_begin');
    $end   = $this->input->post('dt_end');

    if ($begin == '') {
      $begin = date('Y-m-d');
    }

    if ($end == '') {
      $end = '9999-12-31';
    }
    $rows = $this->OrgModel->GetList($begin,$end);
    $data['rows'] = array();
    $i = 0 ;
    foreach ($rows as $row) {
      $temp = array(
        'id'       => $row->id,
        'begda'    => $row->begin_date,
        'endda'    => $row->end_date,
        'name'     => $row->name,
        'viewlink' => anchor($this->ctrlClass.'View/'.$row->id.'/'.$begin.'/'.$end,'View','class="btn btn-link" title="view"'),
      );
      $data['rows'][$i] = $temp;
      $i++;
    }

    $data['begin'] = $begin;
    $data['end']   = $end;
    $data['addLink'] = $this->ctrlClass.'Add';

    $this->parser->parse($this->viewDir.'main_view',$data);
  }

  public function View($id=0,$begin='',$end='')
  {
    if ($id == 0 && $begin == '' && $end == '') {
      $id    = $this->session->userdata('selectId');
      $begin = $this->session->userdata('filterBegDa');
      $end   = $this->session->userdata('filterEndDa');
    } else {
      $array = array(
        'selectId'    => $id,
        'filterBegDa' => $begin,
        'filterEndDa' => $end,
      );
      $this->session->set_userdata($array);
    }
    $keydate['begin'] = $begin;
    $keydate['end']   = $end;

    $obj  = $this->OrgModel->GetByIdRow($id);
    $attr = $this->OrgModel->GetLastName($id,$keydate);
    $data['begin']    = $begin;
    $data['end']      = $end;
    $data['objBegin'] = $obj->begin_date;
    $data['objEnd']   = $obj->end_date;
    $data['objName']  = $attr->name;
    $keydate['begin'] = '1990-01-01';
    $keydate['end']   = '9999-12-31';
    $ls = $this->OrgModel->GetNameHistoryList($id,$keydate,'desc');
    $history = array();
    foreach ($ls as $row) {
      if ($attr->id == $row->id) {
        $class = 'info';
      } else {
        $class = '';
      }
      $history[] = array(
        'historyRow'   => $class,
        'historyBegin' => $row->begin_date,
        'historyEnd'   => $row->end_date,
        'historyName'  => $row->name,
      );
    }
    $data['history']  = $history;
    if ($this->OrgModel->CountParentOrg($id,$keydate)) {
      $parent = $this->OrgModel->GetParentOrg($id,$keydate);
      $data['parentId']   = $parent->parent_id;
      $data['parentName'] = $parent->parent_name;
    } else {
      $data['parentId']   = '';
      $data['parentName'] = '';
    }

    $children = array();
    if ($this->OrgModel->CountChildrenOrg($id,$keydate)) {
      $child = $this->OrgModel->GetChildrenOrgList($id,$keydate);
      foreach ($child as $row) {
        $children[] = array(
          'childrenBegin' => $row->child_begin_date,
          'childrenEnd'   => $row->child_end_date,
          'childrenId'    => $row->child_id,
          'childrenName'  => $row->child_name,
        );
      }
    }
    $data['children'] = $children;
    $post = array();
    if ($this->OrgModel->CountPost($id,$keydate)) {
      $ls = $this->OrgModel->GetPostList($id,$keydate);
      foreach ($ls as $row) {
        $post[] = array(
          'postBegin' => $row->post_begin_date,
          'postEnd'   => $row->post_end_date,
          'postId'    => $row->post_id,
          'postName'  => $row->post_name,
        );
      }
    }
    $data['post']     = $post;
    if ($this->OrgModel->CountChiefPerson($id,$keydate)) {
      $chief = $this->OrgModel->GetLastChiefPerson($id,$keydate);
      $data['chiefPostId']   = $chief->post_id;
      $data['chiefPostName'] = $chief->post_name;
      $data['chiefEmpId']    = $chief->person_id;
      $data['chiefEmpName']  = $chief->person_name;
    } else {
      $chief = $this->OrgModel->GetLastChiefPost($id,$keydate);
      $data['chiefPostId']   = $chief->post_id;
      $data['chiefPostName'] = $chief->post_name;
      $data['chiefEmpId']    = '-';
      $data['chiefEmpName']  = '-';
    }

    $data['backLink'] = $this->ctrlClass;
    $data['delLink']  = $this->ctrlClass.'DeleteProcess';
    $data['editDate'] = $this->ctrlClass.'EditDate/';
    $data['editName'] = $this->ctrlClass.'EditName/';
    $this->parser->parse($this->viewDir.'detail_view',$data);
  }

  public function Breadcrumb($id=0)
  {

  }

  public function Add()
  {
    $begin  = $this->session->userdata('filterBegDa');
    $end    = $this->session->userdata('filterEndDa');
    if (is_null($begin) OR $begin == '') {
      $begin = date('Y-m-d');
    }
    if (is_null($end) OR $end == '') {
      $end = date('Y-m-d');
    }
    $ls     = $this->OrgModel->GetList($begin,$end);
    $parent = array();
    foreach ($ls as $row) {
      $parent[$row->id] = $row->id.' - '.$row->name;
    }
    $data['parentOpt']  = $parent;
    $data['cancelLink'] = $this->ctrlClass;

    $this->load->view($this->viewDir.'add_form',$data);

  }

  public function AddProcess()
  {
    $begin  = $this->input->post('dt_begin');
    $end    = $this->input->post('dt_end');
    $name   = $this->input->post('txt_name');
    $parent = $this->input->post('slc_parent');
    $this->OrgModel->Create($name,$begin,$end,$parent);
    redirect($this->ctrlClass);
  }

  public function EditName()
  {
    $id  = $this->session->userdata('selectId');
    if ($id == '') {
      redirect($this->ctrlClass);
    }
    $old                = $this->OrgModel->GetLastName($id);
    $data['begin']      = date('Y-m-d');
    $data['name']       = $old->name;
    $data['cancelLink'] = $this->ctrlClass.'View/';
    $data['process']    = $this->ctrlClass.'EditNameProcess';
    $this->load->view($this->viewDir.'name_form', $data);

  }

  public function EditNameProcess()
  {
    $validOn = $this->input->post('dt_begin');
    $newName = $this->input->post('txt_name');
    $id      = $this->session->userdata('selectId');
    $this->OrgModel->ChangeName($id,$newName,$validOn,'9999-12-31');
    redirect($this->ctrlClass.'View/'.$id.'/'.$validOn.'/9999-12-31');
  }

  public function EditDate()
  {
    $id  = $this->session->userdata('selectId');
    if ($id == '') {
      redirect($this->ctrlClass);
    }
    $old = $this->OrgModel->GetByIdRow($id);
    $data['end']   = $old->end_date;

    $data['cancelLink'] = $this->ctrlClass.'View/';
    $data['process'] = $this->ctrlClass.'EditDateProcess';
    $this->load->view($this->viewDir.'date_form', $data);

  }

  public function EditDateProcess()
  {
    $id  = $this->session->userdata('selectId');
    $end = $this->input->post('dt_end');
    $this->OrgModel->Delimit($id,$end);
    redirect($this->ctrlClass.'View/');

  }

  public function DeleteProcess()
  {
    $id = $this->session->userdata('selectId');
    $this->OrgModel->Delete($id);
    redirect($this->ctrlClass);

  }
}
