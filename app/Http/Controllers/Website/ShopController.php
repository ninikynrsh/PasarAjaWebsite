<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Shops;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class ShopController extends Controller
{

    public function listOfShop(Shops $shops)
    {
        $listShop = $shops->select()->get();

        // tampilkan $listShop dalam foreach
        foreach ($listShop as &$shop) {
            // get fullname data
            $fullname = User::select('full_name')->where('id_user', $shop->id_user)->first();

            // data full name ditambahkan ke $shop
            $shop->owner_name = $fullname->full_name;
        }

        return response()->json(['status' => 'success', 'message' => 'Data didapatkan', 'data' => $listShop], 200);
    }


    public function prepareCreate(Request $request, Shops $shop)
    {

        // validasi data
        $validator = Validator::make(
            $request->all(),
            [
                'id_user' => 'required',
                'phone_number' => 'nullable|string|regex:/^\d{9,15}$/',
                'shop_name' => 'required|min:4|max:50',
                'description' => 'nullable|max:500',
                'benchmark' => 'required|min:4|max:100',
                'photo_file' => 'required',
            ],
            [
                'id_user.required' => 'ID user tidak boleh kosong',

                'phone_number' => 'Nomor HP tidak valid',
                'shop_name.required' => 'Nama toko tidak boleh kosong',
                'shop_name.min' => 'Nama toko harus terdiri dari minimal 4 karakter',
                'shop_name.max' => 'Nama toko harus terdiri dari maksimal 50 karakter',
                'benchmark.required' => 'Benchmark tidak boleh kosong',
                'benchmark.min' => 'Benchmark harus terdiri dari minimal 4 karakter',
                'benchmark.max' => 'Benchmark harus terdiri dari maksimal 100 karakter',
                'photo_file.required' => 'Photo tidak boleh kosong',
                'description.max' => 'Deskripsi harus terdiri dari maksimal 500 karakter',
            ]
        );


        // cek validasi
        if ($validator->fails()) {
            return ['status' => 'error', 'message' => $validator->errors()->first()];
        }

        // put data
        $shop->id_user = $request->input('id_user');
        $shop->phone_number = $request->input('phone_number');
        $shop->shop_name = $request->input('shop_name');
        $shop->description = $request->input('description');
        $shop->benchmark = $request->input('benchmark');
        $shop->photo = $request->input('photo_file');

        // menyimpan data
        if ($shop->save()) {
            return ['status' => 'success', 'message' => 'Toko berhasil dibuat', 'data' => $shop];
        } else {
            return ['status' => 'error', 'message' => 'Toko gagal dibuat'];
        }
    }

    private function createTableProduct($tableName)
    {
        // create table shop product
        Schema::dropIfExists($tableName);
        Schema::create($tableName, function (Blueprint $table) {
            $table->id('id_product');
            $table->unsignedBigInteger('id_shop');
            $table->unsignedBigInteger('id_cp_prod');
            $table->string('product_name', 50)->unique();
            $table->text('description')->nullable();
            $table->integer('selling_unit');
            $table->enum('unit', ['Gram', 'Kilogram', 'Ons', 'Kuintal', 'Ton', 'Liter', 'Milliliter', 'Sendok', 'Cangkir', 'Bungkus', 'Mangkok', 'Botol', 'Karton', 'Dus', 'Buah', 'Ekor', 'Gelas', 'Piring']);
            $table->integer('price');
            $table->smallInteger('total_sold')->default(0);
            $table->text('settings')->nullable()->default('{"is_recommended": false, "is_shown": true, "is_available": true}');
            $table->string('photo', 15);
            $table->timestamps();
            $table->foreign('id_shop')->references('id_shop')
                ->on('0shops')->onDelete('cascade');
            $table->foreign('id_cp_prod')->references('id_cp_prod')
                ->on('0product_categories')->onDelete('no action');
        });
    }

    private function createTablePromo($tableName, $tableProd)
    {
        // create table shop promo
        Schema::dropIfExists($tableName);
        Schema::create($tableName, function (Blueprint $table) use ($tableProd) {
            $table->id('id_promo');
            $table->unsignedBigInteger('id_shop');
            $table->unsignedBigInteger('id_product');
            $table->integer('promo_price');
            $table->double('percentage');
            $table->date('start_date');
            $table->date('end_date');
            $table->timestamps();
            $table->foreign('id_product')->references('id_product')
                ->on($tableProd)->onUpdate('cascade')->onDelete('cascade');
        });
    }

    private function createTableTransaction($tableName)
    {
        // create table shop transaction
        Schema::dropIfExists($tableName);
        Schema::create($tableName, function (Blueprint $table) {
            $table->id('id_trx');
            $table->unsignedBigInteger('id_user');
            $table->text('order_code')->unique();
            $table->string('order_pin', 4);
            $table->enum('status', ['Request', 'Cancel_Customer', 'Cancel_Merchant', 'Confirmed', 'InTaking', 'Submitted', 'Expired', 'Finished']);
            $table->date('taken_date');
            $table->bigInteger('expiration_time');
            $table->integer('confirmed_by');
            $table->text('canceled_message');
            $table->timestamps();
            $table->foreign('id_user')->references('id_user')
                ->on('0users')->onDelete('cascade');
        });
    }

    private function createTableTransactionDetail($tableName,  $tableTrx, $tableProd)
    {
        // create table shop transaction detail
        Schema::dropIfExists($tableName);
        Schema::create($tableName, function (Blueprint $table) use ($tableTrx, $tableProd) {
            $table->id('id_detail');
            $table->unsignedBigInteger('id_trx');
            $table->unsignedBigInteger('id_product');
            $table->smallInteger('quantity');
            $table->integer('promo_price');
            $table->string('notes', 100)->nullable();
            $table->foreign('id_trx')->references('id_trx')
                ->on('sp_1_trx')->onDelete('cascade');
            $table->foreign('id_product')->references('id_product')
                ->on('sp_1_prod')->onDelete('cascade');
        });
    }

    private function createTableReview($tableName, $tableProd, $tableTrx)
    {
        // create table shop review
        Schema::dropIfExists($tableName);
        Schema::create($tableName, function (Blueprint $table) use ($tableProd, $tableTrx) {
            $table->id('id_review');
            $table->unsignedBigInteger('id_user');
            $table->unsignedBigInteger('id_trx');
            $table->unsignedBigInteger('id_product');
            $table->enum('star', ['1', '2', '3', '4', '5']);
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->foreign('id_trx')->references('id_trx')
                ->on($tableTrx)->onDelete('cascade');
            $table->foreign('id_user')->references('id_user')
                ->on('0users')->onDelete('cascade');
            $table->foreign('id_product')->references('id_product')
                ->on($tableProd)->onUpdate('cascade')->onDelete('cascade');
        });
    }

    private function createTableComplain($tableName, $tableProd, $tableTrx)
    {
        Schema::dropIfExists($tableName);
        Schema::create($tableName, function (Blueprint $table) use ($tableProd, $tableTrx) {
            $table->id('id_complain');
            $table->unsignedBigInteger('id_user');
            $table->unsignedBigInteger('id_shop');
            $table->unsignedBigInteger('id_trx');
            $table->unsignedBigInteger('id_product');
            $table->text('reason');
            $table->timestamps();
            $table->foreign('id_user')->references('id_user')
                ->on('0users')->onDelete('no action');
            $table->foreign('id_shop')->references('id_shop')
                ->on('0shops')->onDelete('cascade');
            $table->foreign('id_trx')->references('id_trx')
                ->on($tableTrx)->onDelete('cascade');
            $table->foreign('id_product')->references('id_product')
                ->on($tableProd)->onUpdate('cascade')->onDelete('cascade');
        });
    }

    public function saveShopPhoto2(Request $request)
    {
        try {
            $photo = $request->file('photo');

            // Mengecek apakah file foto shop valid
            if ($photo && $photo->isValid()) {
                // Mendapatkan format gambar
                $extension = $photo->getClientOriginalExtension();
                // Mengubah nama foto shop
                $fotoShop = time() . '.' . $extension;
                // Menyimpan foto shop
                if (app()->environment('local')) {
                    $photo->move(public_path('shops/'), $fotoShop);
                } else {
                    $isMoved = $photo->move(public_path(base_path('../public_html/public/shops/')), $fotoShop);
                    if (!$isMoved) {
                        return response()->json(['status' => 'error', 'message' => 'Failed to upload the photo.']);
                    }
                }
                // Jika foto shop berhasil disimpan
                $filenamePhoto = $fotoShop;

                // Berhasil upload, kembalikan response success
                return response()->json(['status' => 'success', 'message' => 'Photo uploaded successfully.', 'filename' => $filenamePhoto]);
            } else {
                // Jika $photo bukan file yang valid
                return response()->json(['status' => 'error', 'message' => 'The photo is not valid.']);
            }
        } catch (\Exception $e) {
            $filenamePhoto = 'shop.jpg';
            return response()->json(['status' => 'error', 'message' => 'Failedd to upload the photo.']);
        }
    }

    public function saveShopPhoto(Request $request)
    {
        $photo = $request->file('photo');

        // Mengecek apakah file foto produk valid
        if ($photo && $photo->isValid()) {
            // Mendapatkan format gambar
            $extension = $photo->getClientOriginalExtension();
            // Mengubah nama foto produk
            $fotoProduk = time() . '.' . $extension;
            // Menyimpan foto produk
            if (app()->environment('local')) {
                $photo->move(public_path('prods/'), $fotoProduk);
            } else {
                $isMoved = $photo->move(public_path(base_path('../public_html/public/prods/')), $fotoProduk);
                if (!$isMoved) {
                    return ['status' => 'error', 'message' => 'Gagal menyimpan foto produk'];
                }
            }
            // Jika foto produk berhasil disimpan
            return ['status' => 'success', 'message' => 'Foto produk berhasil disimpan', 'data' => ['filename' => $fotoProduk]];
        } else {
            // Jika $photo bukan file yang valid
            return ['status' => 'error', 'message' => 'File foto tidak valid atau tidak ditemukan'];
        }
    }


    public function createShop(Request $request, Shops $shop)
    {
        $email = $request->input('email');
        $phoneNumber = $request->input('phone_number');
        $request->input('id_user');
        $request->input('shop_name');
        $request->input('description');
        $request->input('benchmark');
        $request->input('operational');
        $request->file('photo');
        $filenamePhoto = $request->input('photo_file', 'shop.jpg');

        // validasi data produk
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'photo' => 'required|file|image|max:512',
        ], [
            'email' => 'Email tidak valid.',
            'photo.required' => 'Foto produk tidak boleh kosong.',
            'photo.max' => 'Ukuran foto produk tidak boleh lebih dari 512 kb',
            'photo.image' => 'File harus berupa gambar.',
        ]);
        // cek validasi
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 400);
        }

        // get id user
        $idUser = User::select('id_user')
            ->where('email', $email)
            ->first();
        if (is_null($idUser)) {
            return response()->json(['status' => 'error', 'message' => 'User tidak ditemukan'], 404);
        }
        // save id user
        $idUser = $idUser->id_user;

        // cek user sudah punya toko atau belum
        $isExistId = $shop::select('id_user')
            ->where('id_user', '=', $idUser)
            ->limit(1)->exists();

        // cek apakah nomor hp sudah exist atau belum
        $isExistPhone = $shop::select('phone_number')
            ->where('phone_number', '=', $phoneNumber)
            ->limit(1)->exists();

        if ($isExistId) {
            return response()->json(['status' => 'error', 'message' => 'User tersebut sudah memiliki Toko'], 400);
        } else if ($isExistPhone) {
            return response()->json(['status' => 'error', 'message' => 'Nomor HP Sudah terdaftar'], 400);
        } else {

            try {
                $photo = $request->file('photo');

                // Mengecek apakah file foto shop valid
                if ($photo && $photo->isValid()) {
                    // Mendapatkan format gambar
                    $extension = $photo->getClientOriginalExtension();
                    // Mengubah nama foto shop
                    $fotoShop = time() . '.' . $extension;
                    // Menyimpan foto shop
                    if (app()->environment('local')) {
                        $photo->move(public_path('shops/'), $fotoShop);
                    } else {
                        $isMoved = $photo->move(public_path(base_path('../public_html/public/shops/')), $fotoShop);
                        if (!$isMoved) {
                            return response()->json(['status' => 'error', 'message' => 'Failed to upload the photo.']);
                        }
                    }
                    // Jika foto shop berhasil disimpan
                    $filenamePhoto = $fotoShop;
                    // Duplikat objek $request
                    $newRequest = $request->duplicate();
                    // Menambahkan data baru ke dalam duplikat
                    $newRequest->merge(['id_user' => $idUser, 'photo_file' => $filenamePhoto]);

                    // prepare to save data
                    $result = $this->prepareCreate($newRequest, $shop);
                    // jika data gagal disimpan
                    if ($result['status'] === 'error') {
                        return response()->json(['status' => 'error', 'message' => $result['message']], 400);
                    }
                    // jika data berhasil disimpan
                    else {
                        // get data toko
                        $shopData = $shop->select()->where('id_user', '=', $idUser)
                            ->limit(1)->first();
                        // generate table name for shop
                        $tableId = 'sp_' . $shopData->id_shop . '_';
                        $tableProduct = $tableId . 'prod';
                        $tableReview = $tableId . 'rvw';
                        $tableComplain = $tableId . 'comp';
                        $tablePromo = $tableId . 'promo';
                        $tableTransaction = $tableId . 'trx';
                        $tabelTransacDetail = $tableId . 'trx_dtl';

                        // create table product
                        $this->createTableProduct($tableProduct);

                        // create table promo
                        $this->createTablePromo($tablePromo, $tableProduct);

                        // create table transaction
                        $this->createTableTransaction($tableTransaction);

                        // create table transaction detail
                        $this->createTableTransactionDetail($tabelTransacDetail, $tableTransaction, $tableProduct);

                        // create table review
                        $this->createTableReview($tableReview, $tableProduct, $tableTransaction);

                        // create table complain
                        $this->createTableComplain($tableComplain, $tableProduct, $tableTransaction);

                        // return response()->json(['status' => 'succcess', 'message' => 'Toko berhasil dibuat', 'data' => $shopData,], 200);

                        // tampilkan notif 'Toko Berhasil Ditambahkan'



                        $request->session()->put('success', 'Toko Berhasil Ditambahkan');

                        $listShop = ShopController::listOfShop(new Shops())->getData()->data;
                        
                        return view('layouts.tambah', ['data' => $listShop]);
                        
                    }
                } else {
                    // Jika $photo bukan file yang valid
                    return response()->json(['status' => 'error', 'message' => 'The photo is not valid.']);
                }
            } catch (\Exception $e) {
                $filenamePhoto = 'shop.jpg';
                return response()->json(['status' => 'error', 'message' => 'Failedd to upload the photo.']);
            }
        }
    }

    public function updateShop(Request $request, Shops $shop)
    {

        // validasi data
        $validator = Validator::make(
            $request->all(),
            [
                'id_shop' => 'required',
                'shop_name' => 'required|min:4|max:50',
                'description' => 'nullable|max:500',
                'benchmark' => 'required|min:4|max:100',
                'photo' => 'required',
            ],
            [
                'id_shop.required' => 'ID toko tidak boleh kosong',
                'shop_name.required' => 'Nama toko tidak boleh kosong',
                'shop_name.min' => 'Nama toko harus terdiri dari minimal 4 karakter',
                'shop_name.max' => 'Nama toko harus terdiri dari maksimal 50 karakter',
                'benchmark.required' => 'Benchmark tidak boleh kosong',
                'benchmark.min' => 'Benchmark harus terdiri dari minimal 4 karakter',
                'benchmark.max' => 'Benchmark harus terdiri dari maksimal 100 karakter',
                'photo.required' => 'Photo tidak boleh kosong',
                'description.max' => 'Deskripsi harus terdiri dari maksimal 500 karakter',
            ]
        );

        // cek validasi
        if ($validator->fails()) {
            return ['status' => 'error', 'message' => $validator->errors()->first()];
        }

        // put new data
        $newData = [
            'shop_name' => $request->input('shop_name'),
            'description' => $request->input('description'),
            'benchmark' => $request->input('benchmark'),
            'photo' => $request->input('photo'),
        ];

        $idShop = $request->input('id_shop');

        // cek toko exist atau tidak
        $isExist = $shop->select('id_shop')
            ->where('id_shop', '=', $idShop)
            ->limit(1)->exists();

        // jika exist
        if ($isExist) {
            // update toko
            $isUpdate = $shop->select('id_shop')
                ->where('id_shop', '=', $idShop)
                ->limit(1)->update($newData);

            // jika toko berhasil diupdate
            if ($isUpdate) {
                return response()->json(['status' => 'success', 'message' => 'Toko berhasil diupdate'], 200);
            } else {
                return response()->json(['status' => 'error', 'message' => 'Toko gagal diupdate'], 400);
            }
        } else {
            return response()->json(['status' => 'error', 'message' => 'Toko tidak ditemukan'], 400);
        }
    }

    public function updateOperational(Request $request, Shops $shop)
    {
        // validasi data
        $validator = Validator::make(
            $request->all(),
            [
                'id_shop' => 'required',
                'operational' => 'required',
            ],
            [
                'id_shop.required' => 'ID toko tidak boleh kosong',
                'operational.required' => 'Jadwal toko tidak boleh kosong',
            ]
        );

        // cek validasi
        if ($validator->fails()) {
            return ['status' => 'error', 'message' => $validator->errors()->first()];
        }

        // put new data
        $newData = [
            'operational' => $request->input('operational'),
        ];

        $idShop = $request->input('id_shop');

        // cek toko exist atau tidak
        $isExist = $shop->select('id_shop')
            ->where('id_shop', '=', $idShop)
            ->limit(1)->exists();

        // jika exist
        if ($isExist) {
            // update toko
            $isUpdate = $shop->select('id_shop')
                ->where('id_shop', '=', $idShop)
                ->limit(1)->update($newData);

            // jika toko berhasil diupdate
            if ($isUpdate) {
                return response()->json(['status' => 'success', 'message' => 'Jadwal buka toko berhasil diupdate'], 200);
            } else {
                return response()->json(['status' => 'error', 'message' => 'Jadwal buka toko gagal diupdate'], 400);
            }
        } else {
            return response()->json(['status' => 'error', 'message' => 'ID Toko tidak ditemukan'], 400);
        }
    }

    public function deleteShop($idShop) // Mengambil ID toko dari parameter route
    {
        $shop = new Shops();

        // cek data exist atau engak
        $isExist = $shop->select('id_shop')
            ->where('id_shop', '=', $idShop)
            ->limit(1)->exists();

            

        // jika data exist
        if ($isExist) {
            // generate table name for shop
            $tableId = 'sp_' . $idShop . '_';
            // return response()->json(['id_shop' =>$tableId ]);
            $tableProduct = $tableId . 'prod';
            $tableReview = $tableId . 'rvw';
            $tableComplain = $tableId . 'comp';
            $tablePromo = $tableId . 'promo';
            $tableTransaction = $tableId . 'trx';
            $tabelTransacDetail = $tableId . 'trx_dtl';

            // // deleting child table
            Schema::dropIfExists($tabelTransacDetail);
            // Schema::dropIfExists($tableTransaction);
            Schema::dropIfExists($tableReview);
            Schema::dropIfExists($tablePromo);
            Schema::dropIfExists($tableComplain);
            Schema::dropIfExists($tableProduct);

            // deleting shop data
            $delete = $shop->select('id_shop')->where('id_shop', '=', $idShop)->delete();

            if ($delete) {
                $request = new Request();
                // $request->session()->put('success', 'Toko Berhasil Ditambahkan');

                $listShop = ShopController::listOfShop(new Shops())->getData()->data;
                
                return view('layouts.tambah', ['data' => $listShop]);
            } else {
                return response()->json(['status' => 'error', 'message' => 'Toko gagal dihapus'], 400);
            }
        } else {
            return response()->json(['status' => 'error', 'message' => 'ID Toko tidak ditemukan'], 400);
        }
    }

    public function getShopData(Request $request, Shops $shops)
    {
        $idShop = $request->input('id_shop');

        $shopData = $shops
            ->select()
            ->where('id_shop', $idShop)
            ->first();

        if (!$shopData) {
            return response()->json(['status' => 'error', 'message' => 'Shop not found'], 404);
        }

        $shopData->photo = asset('shops/' . $shopData->photo);
        return response()->json(['status' => 'success', 'message' => 'data dapat',  'data' => $shopData], 200);
    }

    public function getContact(Request $request)
    {
        $idiShop = $request->input('id_shop');

        $contact = DB::table('0shops as sp')
            ->join('0users as ussr', 'ussr.id_user', 'sp.id_user')
            ->select('ussr.phone_number', 'ussr.email')
            ->where('sp.id_shop', $idiShop)
            ->first();

        return response()->json(['status' => 'success', 'message' => 'Data didapatkan', 'data' => $contact], 200);
    }
}
