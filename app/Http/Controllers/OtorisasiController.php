<?php

namespace App\Http\Controllers;

use App\Models\Otorisasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class OtorisasiController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Otorisasi::get();
            return DataTables::of($data)
                ->addColumn('action', function ($row) {
                    $btn = '<div class="btn-group"><button type="button" data-id="' . $row->id . '" class="btn btn-primary btn-sm btn-edit"><i class="fa fa-eye"></i></button><button type="button" data-id="' . $row->id . '" data-name="' . $row->user_id . '" class="btn btn-danger btn-sm btn-delete"><i class="fa fa-trash"></i></button></div>';
                    return $btn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        $x['title'] = "Data Otorisasi";
        return view('admin/admin', $x);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'name' => 'required',
            'phone' => 'required',
            'address' => 'required',
            'level' => 'required',
            'status' => 'required',
        ]);

        if ($validator->fails()) {
            return redirect('admin/admin')
                ->withErrors($validator)
                ->withInput();
        }

        if (empty($request->input('password'))) {
            $data = [
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'phone' => $request->input('phone'),
                'address' => $request->input('address'),
                'level' => $request->input('level'),
                'status' => $request->input('status')
            ];
        } else {
            $data = [
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'phone' => $request->input('phone'),
                'address' => $request->input('address'),
                'password' => Hash::make($request->input('password')),
                'level' => $request->input('level'),
                'status' => $request->input('status')
            ];
        }
        Otorisasi::where('id', $request->input('id'))->update($data);
        session()->flash('type', 'success');
        session()->flash('notif', 'Data berhasil disimpan');
        return redirect('admin/admin');
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required',
            'email' => 'required|email|unique:admins',
            'name' => 'required',
            'address' => 'required',
            'level' => 'required',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return redirect('admin/admin')
                ->withErrors($validator)
                ->withInput();
        }

        $data = [
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),
            'address' => $request->input('address'),
            'password' => Hash::make($request->input('password')),
            'level' => $request->input('level'),
            'created_at' => now()
        ];
        Otorisasi::insert($data);
        session()->flash('type', 'success');
        session()->flash('notif', 'Data berhasil ditambah');
        return redirect('admin/admin');
    }

    public function data(Request $request)
    {
        echo json_encode(Otorisasi::where(['id' => $request->input('id')])->first());
    }

    public function delete(Request $request)
    {
        $id = $request->input('id');
        Otorisasi::where(['id' => $id])->delete();
        session()->flash('notif', 'Data berhasil dihapus');
        session()->flash('type', 'success');
        return redirect('admin/admin');
    }

    public function auth(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'password' => 'required',
            'g-recaptcha-response' => 'required'
        ]);

        if ($validator->fails()) {
            return redirect('admin/login')
                ->withErrors($validator)
                ->withInput();
        }
        $user_id = $request->input('user_id');
        $password = $request->input('password');
        $response_key = $request->input('g-recaptcha-response');
        $secret_key = env('GOOGLE_RECHATPTCHA_SECRETKEY');

        $verify = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . $secret_key . '&response=' . $response_key);
        $response = json_decode($verify);

        $data = Otorisasi::where(['user_id' => $user_id]);
        if ($response->success) {
            if ($data->count() == 1) {
                $data = $data->first();
                if ($password == $data->password) {
                    session([
                        'id' => $data->id,
                        'user_id' => $data->user_id,
                        'password' => $data->password,
                        'opd' => $data->opd,
                        'otorisasi' => $data->otorisasi,
                        'login_status' => true
                    ]);
                    session()->flash('notif', 'Selamat Datang ' . $data->user_id);
                    session()->flash('type', 'info');
                    return redirect('admin');
                } else {
                    session()->flash('type', 'error');
                    session()->flash('notif', 'User ID atau password anda tidak sesuai');
                }
            } else {
                session()->flash('type', 'error');
                session()->flash('notif', 'User ID atau password anda tidak sesuai');
            }
        }else{
            session()->flash('type', 'error');
            session()->flash('notif', 'Ups! Sepertinya ada yang salah');
        }
        return redirect('admin/login');
    }

    public function logout()
    {
        session()->flash('type', 'info');
        session()->flash('notif', 'Sampai jumpa ' . session('name'));
        session()->forget(['id', 'name', 'login_status']);
        return redirect('admin/login');
    }
}