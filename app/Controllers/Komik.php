<?php

namespace App\Controllers;

use App\Models\KomikModel;

class Komik extends BaseController
{
    protected $komikModel;

    public function __construct()
    {
        helper('form');
        $this->komikModel = new KomikModel();
    }

    public function index()
    {
        $data = [
            'title' => 'Daftar Komik',
            'komik' => $this->komikModel->getKomik()
        ];

        return view('komik/index', $data);
    }

    public function detail($slug)
    {
        $data = [
            'title' => 'Detail Komik',
            'komik' => $this->komikModel->getKomik($slug)
        ];
        //jika tdk valid
        if (empty($data['komik'])) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Judul komik ' . $slug . ' tidak ditemukan.');
        }
        return view('komik/detail', $data);
    }

    public function create()
    {
        $data = [
            'title' => 'Tambah Komik',
            'validation' => \Config\Services::validation()
        ];
        return view('komik/create', $data);
    }

    public function save()
    {
        if (!$this->validate([
            'judul' => [
                'rules' => 'required|is_unique[komik.judul]',
                'errors' => [
                    'required' => '{field} komik harus diisi.',
                    'is_unique' => '{field} komik sudah ada.'
                ]
            ],
            'sampul' => [
                'rules' => 'max_size[sampul,1024]|is_image[sampul]|mime_in[sampul,image/jpg,image/jpeg,image/png]',
                'errors' => [
                    'max_size' => 'Ukuran gambar terlalu besar.',
                    'is_image' => 'Harus file gambar.',
                    'mime_in' => 'Hanya diperbolehkan gambar format jpg/jpeg/png.'
                ]
            ]
        ])) {
            return redirect()->to('/komik/create')->withInput();
        }
        //ambil gambar
        $fileSampul = $this->request->getFile('sampul');
        //default gambar
        if ($fileSampul->getError() == 4) {
            $namaGambar = 'default.png';
        } else {
            //nama random
            $namaGambar = $fileSampul->getRandomName();
            //pindah file ke folder img
            $fileSampul->move('img', $namaGambar);
        }

        $slug = url_title($this->request->getVar('judul'), '-', true);
        $this->komikModel->save([
            'judul' => $this->request->getVar('judul'),
            'slug' => $slug,
            'penulis' => $this->request->getVar('penulis'),
            'penerbit' => $this->request->getVar('penerbit'),
            'sampul' => $namaGambar
        ]);

        session()->setFlashdata('pesan', 'Data berhasil ditambahkan.');
        return redirect()->to('/komik');
    }

    public function delete($id)
    {
        //cari gambar berdasarkan id
        $komik = $this->komikModel->find($id);
        //cek default gambar
        if ($komik['sampul'] != 'default.png') {
            //hapus gambar
            unlink('img/' . $komik['sampul']);
        }

        $this->komikModel->delete($id);
        session()->setFlashdata('pesan', 'Data berhasil dihapus.');
        return redirect()->to('/komik');
    }

    public function edit($slug)
    {
        $data = [
            'title' => 'Tambah Komik',
            'validation' => \Config\Services::validation(),
            'komik' => $this->komikModel->getKomik($slug)
        ];
        return view('komik/edit', $data);
    }

    public function update($id)
    {
        $komikLama = $this->komikModel->getKomik($this->request->getVar('slug'));
        if ($komikLama['judul'] == $this->request->getVar('judul')) {
            $roleJudul = 'required';
        } else {
            $roleJudul = 'required|is_unique[komik.judul]';
        }

        if (!$this->validate([
            'judul' => [
                'rules' => $roleJudul,
                'errors' => [
                    'required' => '{field} komik harus diisi.',
                    'is_unique' => '{field} komik sudah ada.'
                ]
            ],
            'sampul' => [
                'rules' => 'max_size[sampul,1024]|is_image[sampul]|mime_in[sampul,image/jpg,image/jpeg,image/png]',
                'errors' => [
                    'max_size' => 'Ukuran gambar terlalu besar.',
                    'is_image' => 'Harus file gambar.',
                    'mime_in' => 'Hanya diperbolehkan gambar format jpg/jpeg/png.'
                ]
            ]
        ])) {
            return redirect()->to('/komik/create')->withInput();
        }
        $fileSampul = $this->request->getFile('sampul');
        //cek gambar(ganti/tidak)
        if ($fileSampul->getError() == 4) {
            $namaGambar = $this->request->getVar('sampulLama');
        } else {
            //random name
            $namaGambar = $fileSampul->getRandomName();
            //pindahkan gambar
            $fileSampul->move('img', $namaGambar);
            //hapus gambar lama
            unlink('img/' . $this->request->getVar('sampulLama'));
        }

        $slug = url_title($this->request->getVar('judul'), '-', true);
        $this->komikModel->save([
            'id' => $id,
            'judul' => $this->request->getVar('judul'),
            'slug' => $slug,
            'penulis' => $this->request->getVar('penulis'),
            'penerbit' => $this->request->getVar('penerbit'),
            'sampul' => $namaGambar
        ]);
        session()->setFlashdata('pesan', 'Data berhasil diubah.');
        return redirect()->to('/komik');
    }
}
