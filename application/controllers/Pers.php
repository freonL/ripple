<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Pers extends CI_Controller{

  private $viewDir   = 'pers/';
  private $ctrlClass = 'Pers/';
  public function __construct()
  {
    parent::__construct();
    $this->load->model('PersModel'); // BaseModel is included
  }

  function index()
  {
    $this->session->unset_userdata('selectId');
    $begin = $this->input->post('dt_begin');
    $end   = $this->input->post('dt_end');

    if ($begin == '') {
      $begin = $this->session->userdata('filterBegDa');
    }

    if ($end == '') {
      $end   = $this->session->userdata('filterEndDa');
    }
    $rows = $this->PersModel->GetList($begin,$end);
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

  public function Add()
  {
    $this->load->model('PostModel');
    $ls    = $this->PostModel->GetList(date('Y-m-d'),date('Y-m-d'));
    $post  = array(''=>'');
    foreach ($ls as $row) {
      $post[$row->id] = $row->id.' - '.$row->name;
    }
    $data['postOpt']    = $post;
    $data['postSlc']    = '';
    $data['cancelLink'] = $this->ctrlClass;

    $data['process'] = $this->ctrlClass.'AddProcess';
    $this->load->view($this->viewDir.'add_form',$data);
  }

  public function AddPost()
  {
    $this->load->model('PostModel');
    $begin = $this->session->userdata('filterBegDa');
    $end   = $this->session->userdata('filterEndDa');
    $ls    = $this->PostModel->GetList($begin,$end);
    $post  = array();
    foreach ($ls as $row) {
      $post[$row->id] = $row->id.' - '.$row->name;
    }
    $data['postOpt']    = $post;
    $data['postSlc']    = '';
    $data['begin']      = date('Y-m-d');
    $data['end']        = '9999-12-31';
    $data['cancelLink'] = $this->ctrlClass.'View/';
    $data['process']    = $this->ctrlClass.'AddPostProcess';
    $this->load->view($this->viewDir.'post_form',$data);

  }

  public function AddPostProcess()
  {
    $persId = $this->session->userdata('selectId');;
    $begin  = $this->input->post('dt_begin');
    $end    = $this->input->post('dt_end');
    $postId = $this->input->post('slc_post');
    $this->PersModel->AddPost($persId,$postId,$begin,$end);
    redirect($this->ctrlClass);
  }

  public function AddProcess()
  {
    $begin = $this->input->post('dt_begin');
    $end   = $this->input->post('dt_end');
    $name  = $this->input->post('txt_name');
    $this->PersModel->Create($name,$begin,$end);
    redirect($this->ctrlClass);
  }

  public function DeleteProcess()
  {
    $id = $this->session->userdata('selectId');
    $this->BaseModel->Delete($id);
    redirect($this->ctrlClass);

  }

  public function DeleteRelProcess($relId=0)
  {
    $this->PersModel->DeleteRel($relId);
    redirect($this->ctrlClass.'View/');
  }

  public function EditDate()
  {
    $id  = $this->session->userdata('selectId');
    if ($id == '') {
      redirect($this->ctrlClass);
    }
    $old = $this->PersModel->GetByIdRow($id);
    $data['begin'] = $old->begin_date;
    $data['end']   = $old->end_date;

    $data['cancelLink'] = $this->ctrlClass.'View/';
    $data['hidden']  = array();
    $data['process'] = $this->ctrlClass.'EditDateProcess';
    $this->load->view($this->viewDir.'date_form', $data);

  }

  public function EditDateProcess()
  {
    $id  = $this->session->userdata('selectId');
    $end = $this->input->post('dt_end');
    $this->PersModel->Delimit($id,$end);
    redirect($this->ctrlClass.'View/');
  }

  public function EditName()
  {
    $id  = $this->session->userdata('selectId');
    if ($id == '') {
      redirect($this->ctrlClass);
    }
    $old                = $this->PersModel->GetLastName($id);
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
    $this->PersModel->ChangeName($id,$newName,$validOn,'9999-12-31');
    redirect($this->ctrlClass.'View/'.$id.'/'.$validOn.'/9999-12-31');
  }

  public function EditRel($relId=0)
  {
    $data['hidden']  = array(
      'rel_id' => $relId
    );
    $old = $this->PersModel->GetRelByIdRow($relId);
    $data['process'] = $this->ctrlClass.'EditRelProcess';
    $data['begin']   = $old->begin_date;
    $data['end']     = $old->end_date;
    $data['cancelLink'] = $this->ctrlClass.'View/';

    $this->load->view($this->viewDir.'date_form', $data);
  }

  public function EditRelProcess()
  {
    $relId = $this->input->post('rel_id');
    $begin = $this->input->post('dt_begin');
    $end   = $this->input->post('dt_end');
    $this->PersModel->ChangeRelDate($relId,$begin,$end);
    redirect($this->ctrlClass.'View/');
  }

  public function View($id=0,$begin='',$end='')
  {
    $delimit = site_url($this->ctrlClass.'EditRel/');
    $remove  = site_url($this->ctrlClass.'DeleteRelProcess/');
    $sprLk   = site_url($this->ctrlClass.'ViewSpr/');

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

    $obj  = $this->PersModel->GetByIdRow($id);
    $attr = $this->PersModel->GetLastName($id,$keydate);
    $data['begin']    = $begin;
    $data['end']      = $end;
    $data['objBegin'] = $obj->begin_date;
    $data['objEnd']   = $obj->end_date;
    $data['objName']  = $attr->name;
    $keydate['begin'] = $begin;
    $keydate['end']   = $end;
    $ls =  $this->PersModel->GetNameHistoryList($id,$keydate,'desc');
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

    $ls = $this->PersModel->GetPostList($id,$keydate,'desc');
    $post = array();
    foreach ($ls as $row) {
      $post[] = array(
        'postRelId' => $row->post_rel_id,
        'postBegin' => $row->post_begin_date,
        'postEnd'   => $row->post_end_date,
        'postId'    => $row->post_id,
        'postName'  => $row->post_name,
        'chgRel'    => $delimit.$row->post_rel_id,
        'remRel'    => $remove.$row->post_rel_id,
        'sprLink'   => $sprLk.$row->post_rel_id,
      );
    }
    $data['post']     = $post;
    $data['backLink'] = $this->ctrlClass;
    $data['delLink']  = $this->ctrlClass.'DeleteProcess';
    $data['addPost']  = $this->ctrlClass.'AddPost/';
    $data['editDate'] = $this->ctrlClass.'EditDate/';
    $data['editName'] = $this->ctrlClass.'EditName/';
    $this->parser->parse($this->viewDir.'detail_view',$data);
  }

  public function ViewSpr($relId=0)
  {
    $this->load->model(array('PostModel'));
    $rel = $this->PersModel->GetRelByIdRow($relId);
    $keydate['begin'] = $this->session->userdata('filterBegDa');
    $keydate['end']   = $this->session->userdata('filterEndDa');
    $persObj = $this->PersModel->GetByIdRow($rel->obj_bottom_id,$keydate);
    $persAtr = $this->PersModel->GetLastName($rel->obj_bottom_id,$keydate);
    $postAtr = $this->PostModel->GetLastName($rel->obj_top_id,$keydate);

    $spr     = $this->PostModel->GetLastSuperiorPerson($rel->obj_top_id,$keydate);
    $data['backLink']    = $this->ctrlClass.'View/';
    $data['persId']      = $rel->obj_bottom_id;
    $data['persName']    = $persAtr->name;
    $data['postId']      = $rel->obj_top_id;
    $data['postName']    = $postAtr->name;
    $data['sprPersId']   = $spr->person_id;
    $data['sprPersName'] = $spr->person_name;
    $data['sprPostId']   = $spr->post_id;
    $data['sprPostName'] = $spr->post_name;
    $this->parser->parse($this->viewDir.'superior_view',$data);
  }

}