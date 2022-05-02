<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Admin extends MY_Controller {

    public function __construct() {
        parent::__construct();
        date_default_timezone_set('Asia/Kolkata');
        $this->form_validation->set_error_delimiters("<p style='color:red' class='error'>", "</p>");

        error_reporting("1");
    }

    public function index() {
        $total_salon = $this->adminmodel->_getTotalProducts();
        $total_barber = $this->adminmodel->_getTotalPackages();
        $total_users = $this->adminmodel->_getTotalUsers();

        $this->load->view('admin/index', ['totalProduct' => $total_salon, 'totalUsers' => $total_users, 'totalPackage' => $total_barber]);
    }

    public function login() {
        $post = $this->input->post();

        if (!empty($post)) {
            $this->form_validation->set_error_delimiters("<p style='color:red' class='error'>", "</p>");
            if ($this->form_validation->run('adminLogin')) {
                $username = $this->input->post('username');
                $password = $this->encode($this->input->post('password'));
                if ($this->adminmodel->_userLogin($username, $password)) {
                    $this->session->set_userdata('userData', $username);
                    $this->session->set_flashdata('success', 'Login succesfully.');
                    return redirect('admin');
                } else {
                    $this->session->set_flashdata('warning', 'Username/Password invalid!');
                    return redirect('admin/login');
                }
            } else {
                $this->load->view('admin/login');
            }
        } else {
            $this->load->view('admin/login');
        }
    }

    public function logout() {
        $this->session->sess_destroy();
        return redirect('admin/login');
    }

    public function forgotPass() {
        $post = $this->input->post();
        $this->form_validation->set_rules('email', 'Email', 'required|trim|valid_email');
        $email = $post['email'];
        if ($this->form_validation->run()) {
            if ($result = $this->adminmodel->checkEmail($post)) {
                $encrypt = $this->encode($result->id);
                $date = $this->encode(date('Y-m-d'));
                $to = $result->email;
                $url = "http://virgo.tecocraft.com/admin/passwordreset/{$encrypt}/{$date}";

                $subject = "Forget Password";
                $body = 'Hello ' . $result->firstname . ' ' . $result->lastname . ',' . "\r\n";
                $body.="<br/>You are receiving this email because you requested a password reset for your Virgo App account. Please click on the following link to set a new password: <a href='" . $url . "'> Change Password</a>" . "\r\n";
                $body.='<br/>If you received this e-mail by mistake please just discard it and no changes will be made to your account.';
                $body.='<br><br>Thanks & Regards';
                $body.='<br>Virgo Support Team';
                if ($res = $this->sendMail($to, $subject, $body)) {
                    $this->session->set_flashdata('success', "Password reset information successfully send on $email.");
                    return redirect('admin/login');
                } else {
                    $this->session->set_flashdata('warning', $res . '<br/>Something went wrong! please try after some time!');
                    return redirect('admin/login');
                }
            } else {
                $this->session->set_flashdata('error', "$email email not found!");
                return redirect('admin/login');
            }
        } else {
            return redirect('admin/login');
        }
    }

    public function passwordreset() {
        $id = $this->uri->segment(3);
        $date = $this->uri->segment(4);
        $post = $this->input->post();
        if (isset($id)) {
            $reqDate = $this->decode($date);
            if ($reqDate === date('Y-m-d')) {
                $this->session->set_userdata('decrypId', $id);
                $decrypt = $this->decode($id);
                $this->load->view('admin/passwordreset', ['decrypt' => $decrypt]);
            } else {
                $this->session->set_flashdata('error', "Password reset link expired!");
                return redirect('admin/login');
            }
        } elseif (isset($post) && count($post) > 0) {
            $post['password'] = $this->encode($post['password']);
            $this->form_validation->set_error_delimiters("<p style='color:red' class='error'>", "</p>");
            if ($this->form_validation->run('resetPass')) {
                if ($this->adminmodel->_updatePassword($post)) {
                    $this->session->set_flashdata('success', "Password successfully reset");
                    return redirect('admin/login');
                } else {
                    $this->session->set_flashdata('danger', "Password reset failed!");
                    return redirect('admin/login');
                }
            } else {
                $this->session->set_flashdata('warning', validation_errors());
                $this->load->view('admin/passwordreset', ['decrypt' => $this->session->userdata('decrypId')]);
            }
        } else {
            $this->load->view('404');
        }
    }

    /*
     * Admin Profile
     */

    public function profile() {
        $post = $this->input->post();
        if (!empty($post) && $this->form_validation->run('profile')) {
            if ($this->adminmodel->_updateProfile($post)) {
                $this->session->set_flashdata('success', 'Profile info successfully update.');
                return redirect('admin/profile');
            } else {
                $this->session->set_flashdata('warning', 'Something went wrong!');
                return redirect('admin/profile');
            }
        } else {
            $profile = $this->adminmodel->_getProfile();
            $this->load->view('admin/profile', ['profile' => $profile]);
        }
    }

    public function changepassword() {
        $post = $this->input->post();
        if (!empty($post['btnSub']) && $this->form_validation->run('changepassword')) {
            $post['oldpassword'] = $this->encode($post['oldpassword']);
            $post['password'] = $this->encode($post['password']);
            if ($this->adminmodel->_isOldPasswordMatch($post)) {
                if ($this->adminmodel->_updatePassword($post)) {
                    $this->session->set_flashdata('success', 'Password successfully changed.');
                    return redirect('admin/changepassword');
                } else {
                    $this->session->set_flashdata('warning', 'Something went wrong! please try again.');
                    return redirect('admin/changepassword');
                }
            } else {
                $this->session->set_flashdata('error', 'Invalid old password!');
                return redirect('admin/changepassword');
            }
        } else {
            $profile = $this->adminmodel->_getProfile();
            $this->load->view('admin/changepassword', ['profile' => $profile]);
        }
    }

    /*
     * Products
     */

    public function products() {
        $config = [
            'upload_path' => 'assets/upload/',
            'allowed_types' => 'jpg|png|jpeg|gif'
        ];
        $this->load->library('upload', $config);
        $post = $this->input->post();
        if (!empty($post)) {
            unset($post['btnSub']);
            if ($this->upload->do_upload('image')) {
                $image = $this->upload->data();
                if (!empty($image['raw_name']) && !empty($image['file_ext'])) {
                    $img_path = $image['raw_name'] . $image['file_ext'];
                    $post['image'] = $img_path;
                }
            }
            if ($this->upload->do_upload('image1')) {
                $image = $this->upload->data();
                if (!empty($image['raw_name']) && !empty($image['file_ext'])) {
                    $img_path = $image['raw_name'] . $image['file_ext'];
                    $post['image1'] = $img_path;
                }
            }

            if ($this->upload->do_upload('image2')) {
                $image1 = $this->upload->data();
                if (!empty($image1['raw_name']) && !empty($image1['file_ext'])) {
                    $img_path1 = $image1['raw_name'] . $image1['file_ext'];
                    $post['image2'] = $img_path1;
                }
            }

            if ($this->upload->do_upload('image3')) {
                $image2 = $this->upload->data();
                if (!empty($image2['raw_name']) && !empty($image2['file_ext'])) {
                    $img_path2 = $image2['raw_name'] . $image2['file_ext'];
                    $post['image3'] = $img_path2;
                }
            }
            if ($this->upload->do_upload('image4')) {
                $image3 = $this->upload->data();
                if (!empty($image3['raw_name']) && !empty($image3['file_ext'])) {
                    $img_path3 = $image3['raw_name'] . $image3['file_ext'];
                    $post['image4'] = $img_path3;
                }
            }
            if ($this->adminmodel->_postProduct($post)) {
                $this->session->set_flashdata('success', 'Product successfully save.');
                return redirect('admin/products');
            } else {
                $this->session->set_flashdata('warning', 'Something went wrong!');
                return redirect('admin/products');
            }
        } else {
            $nationality = $this->adminmodel->_cmbNationality();
            $location = $this->adminmodel->_cmbLocation();
            $products = $this->adminmodel->_getProducts();
            $this->load->view('admin/products', ['products' => $products, 'nationality' => $nationality, 'location' => $location]);
        }
    }

    public function editProduct() {
        $post = $this->input->post();
        $data = $this->adminmodel->_editProduct($post);
        echo json_encode($data);
    }

    public function deleteProduct() {
        $post = $this->input->post();
        $data = $this->adminmodel->_deleteProduct($post);
        echo json_encode($data);
    }

    public function packages() {
        $config = [
            'upload_path' => 'assets/upload/',
            'allowed_types' => 'jpg|png|jpeg|gif'
        ];
        $this->load->library('upload', $config);
        $post = $this->input->post();
        if (!empty($post)) {
            unset($post['btnSub']);
            if ($this->upload->do_upload('image')) {
                $image = $this->upload->data();
                if (!empty($image['raw_name']) && !empty($image['file_ext'])) {
                    $img_path = $image['raw_name'] . $image['file_ext'];
                    $post['image'] = $img_path;
                }
            }

            if ($this->adminmodel->_postPackages($post)) {
                $this->session->set_flashdata('success', 'Pacakge successfully save.');
                return redirect('admin/packages');
            } else {
                $this->session->set_flashdata('warning', 'Something went wrong!');
                return redirect('admin/packages');
            }
        } else {
            $product = $this->adminmodel->_cmbProducts();
            $packages = $this->adminmodel->_getPackages();
            $this->load->view('admin/packages', ['packages' => $packages, 'products' => $product]);
        }
    }

    public function editPackage() {
        $post = $this->input->post();
        $data = $this->adminmodel->_editPackage($post);
        echo json_encode($data);
    }

    public function deletePackage() {
        $post = $this->input->post();
        $data = $this->adminmodel->_deletePackage($post);
        echo json_encode($data);
    }

    /*
     * Users
     */

    public function users() {
        $users = $this->adminmodel->_getUsers();
        $this->load->view('admin/users', ['users' => $users]);
    }

    /*
     * Configuration
     */

    public function configuration() {
        $nationality = $this->adminmodel->_getNationality();
        $location = $this->adminmodel->_getLocation();
//        $callAction = $this->adminmodel->_getCallAction();
        $this->load->view('admin/configuration', ['nationality' => $nationality, 'location' => $location]);
    }

    public function postNationality() {
        $post = $this->input->post();
        $this->form_validation->set_rules('nationality', 'Nationality', 'required|trim|is_unique[tbl_nationality.nationality]');
        if ($this->form_validation->run()) {
            if ($this->adminmodel->_postNationality($post)) {
                $this->session->set_flashdata('success', 'Nationality successfully save.');
                return redirect('admin/configuration');
            } else {
                $this->session->set_flashdata('warning', 'Something went wrong!');
                return redirect('admin/configuration');
            }
        } else {
            $this->session->set_flashdata('warning', 'Nationality already exist!');
            return redirect('admin/configuration');
        }
    }

    public function editNationality() {
        $post = $this->input->post();
        $data = $this->adminmodel->_editNationality($post);
        echo json_encode($data);
    }

    public function deleteNationality() {
        $post = $this->input->post();
        $data = $this->adminmodel->_deleteNationality($post);
        echo json_encode($data);
    }

    public function postLocation() {
        $post = $this->input->post();
        $this->form_validation->set_rules('location', 'Location', 'required|trim|is_unique[tbl_location.location]');
        if ($this->form_validation->run()) {
            if ($this->adminmodel->_postLocation($post)) {
                $this->session->set_flashdata('success', 'Location successfully save.');
                return redirect('admin/configuration');
            } else {
                $this->session->set_flashdata('warning', 'Something went wrong!');
                return redirect('admin/configuration');
            }
        } else {
            $this->session->set_flashdata('warning', 'Location already exist!');
            return redirect('admin/configuration');
        }
    }

    public function editLocation() {
        $post = $this->input->post();
        $data = $this->adminmodel->_editLocation($post);
        echo json_encode($data);
    }

    public function deleteLocation() {
        $post = $this->input->post();
        $data = $this->adminmodel->_deleteLocation($post);
        echo json_encode($data);
    }

    public function calltoaction() {
        $post = $this->input->post();
        if (!empty($post['whatsapp']) || !empty($post['callnow'])) {
            unset($post['btnSub']);
            if ($this->adminmodel->_postCallToAction($post)) {
                $this->session->set_flashdata('success', 'Data successfully save.');
                return redirect('admin/configuration');
            } else {
                $this->session->set_flashdata('warning', 'Something went wrong!');
                return redirect('admin/configuration');
            }
        }
    }

    public function ctalog() {
        $ctalog = $this->adminmodel->_getCTALog();
        $this->load->view('admin/activitylog', ['ctalog' => $ctalog]);
    }

    public function featureImage() {
        $config = [
            'upload_path' => 'assets/upload/',
            'allowed_types' => 'jpg|png|jpeg|gif'
        ];
        $this->load->library('upload', $config);
        $post = $this->input->post();

        $this->form_validation->set_rules('title', 'Title', 'required|trim');
        if (isset($post['btnSub']) && $this->form_validation->run()) {
            $this->upload->do_upload('image');
            $image = $this->upload->data();
            if (!empty($image['raw_name']) && !empty($image['file_ext'])) {
                $img_path = $image['raw_name'] . $image['file_ext'];
                $post['image'] = $img_path;
                unlink('assets/upload/' . $post['oldImg']);
            } else {
                $post['image'] = !empty($post['oldImg']) ? $post['oldImg'] : '';
            }
            if ($this->adminmodel->_postImages($post)) {
                $this->session->set_flashdata('success', 'Image successfully save.');
                return redirect('admin/feature-images');
            } else {
                $this->session->set_flashdata('warning', 'Something went wrong!');
                return redirect('admin/feature-images');
            }
        } else {
            $images = $this->adminmodel->_getImages();
            $this->load->view('admin/featureimage', ['images' => $images]);
        }
    }

    public function editImage() {
        $post = $this->input->post();
        $data = $this->adminmodel->_editImage($post);
        echo json_encode($data);
    }

    public function deleteImage() {
        $post = $this->input->post();
        $data = $this->adminmodel->_deleteImage($post);
        echo json_encode($data);
    }

    public function save_image()
    {
        $pid = $this->input->post('pid');
        $image_id = $this->input->post('image_id');

        $this->adminmodel->checkthumbimage($pid);
        $query = $this->db->get();
        $viewthumbimage = $query->result();

        foreach($viewthumbimage as $object)
        {
            $data = $object->$image_id;

            if($data != '')
            {
                unlink("./assets/upload/" . $data);
            }

            $data = $_POST['image'];

            list($type, $data) = explode(';', $data);
            list(, $data)      = explode(',', $data);

            $data = base64_decode($data);
            $imageName = time().'.png';
            file_put_contents('assets/upload/'.$imageName, $data);
            
            $data = array (
           
              $image_id => $imageName,

            );

            $this->adminmodel->updatethumbimage($pid,$data);
        }
    }

    public function view_crop_image()
    {
        $pid = $this->input->post('pid');
        $image_id = $this->input->post('image_id');

        $this->adminmodel->checkthumbimage($pid);
        $query = $this->db->get();
        $viewcropimage = $query->result();

        foreach($viewcropimage as $object)
        {
            $img_src = $object->$image_id;
            $data['viewcropimage'] = base_url("assets/upload/" . $img_src);
        }

        echo json_encode($data);
    }

}
