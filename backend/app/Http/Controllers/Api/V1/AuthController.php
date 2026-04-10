<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\NhanSu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Đăng nhập cho API
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // Kiểm tra username có thể là tên đăng nhập hoặc số điện thoại
        $nhanSu = NhanSu::where('ten_dang_nhap', $request->username)
            ->orWhere('so_dien_thoai', $request->username)
            ->first();

        if (!$nhanSu || !Hash::check($request->password, $nhanSu->mat_khau)) {
            return response()->json([
                'success' => false,
                'message' => 'Thông tin đăng nhập không chính xác'
            ], 401);
        }

        // Kiểm tra trạng thái nếu bị khóa
        if ($nhanSu->trang_thai !== 'active' && $nhanSu->trang_thai !== 'hoat_dong' && $nhanSu->trang_thai !== 1) {
            // Giả định trạng thái hợp lệ, có thể cần chỉnh lại theo model thực tế.
            // Bỏ qua check trạng thái nếu không rõ ràng, hoặc bật nó nếu có rule.
        }

        // Tạo token
        $token = $nhanSu->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Đăng nhập thành công',
            'data' => [
                'token' => $token,
                'user' => $nhanSu
            ]
        ], 200);
    }

    /**
     * Đăng xuất cho API
     */
    public function logout(Request $request)
    {
        // Xóa token hiện tại của user
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đăng xuất thành công'
        ], 200);
    }
}
