<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\NhanSu;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class NhanSuController extends Controller
{
    /**
     * Danh sách nhân sự
     */
    public function index(Request $request): JsonResponse
    {
        $query = NhanSu::query();

        if ($request->has('vai_tro') && $request->vai_tro) {
            $query->where('vai_tro', $request->vai_tro);
        }

        if ($request->has('trang_thai') && $request->trang_thai) {
            $query->where('trang_thai', $request->trang_thai);
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ho_ten', 'LIKE', "%{$search}%")
                  ->orWhere('ten_dang_nhap', 'LIKE', "%{$search}%")
                  ->orWhere('so_dien_thoai', 'LIKE', "%{$search}%");
            });
        }

        $nhanSus = $query->orderBy('ho_ten')->get()->map(function ($ns) {
            return [
                'ma_nhan_su' => $ns->ma_nhan_su,
                'ten_dang_nhap' => $ns->ten_dang_nhap,
                'ho_ten' => $ns->ho_ten,
                'so_dien_thoai' => $ns->so_dien_thoai,
                'vai_tro' => $ns->vai_tro,
                'trang_thai' => $ns->trang_thai,
                'created_at' => $ns->created_at ? $ns->created_at->format('d/m/Y') : null,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $nhanSus
        ]);
    }

    /**
     * Thêm nhân sự
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'ma_nhan_su' => 'required|string|unique:nhan_sus,ma_nhan_su',
            'ten_dang_nhap' => 'required|string|unique:nhan_sus,ten_dang_nhap',
            'ho_ten' => 'required|string|max:255',
            'so_dien_thoai' => 'required|string|max:20',
            'vai_tro' => 'required|string|in:quan_ly,nhan_vien',
            'trang_thai' => 'required|string',
            'mat_khau' => 'nullable|string|min:6',
            'ma_pin' => 'nullable|string|min:4|max:6',
        ]);

        $nhanSu = NhanSu::create([
            'ma_nhan_su' => $request->input('ma_nhan_su'),
            'ten_dang_nhap' => $request->input('ten_dang_nhap'),
            'mat_khau' => $request->input('mat_khau') ? Hash::make($request->input('mat_khau')) : null,
            'ma_pin' => $request->input('ma_pin') ? Hash::make($request->input('ma_pin')) : null,
            'ho_ten' => $request->input('ho_ten'),
            'so_dien_thoai' => $request->input('so_dien_thoai'),
            'vai_tro' => $request->input('vai_tro'),
            'trang_thai' => $request->input('trang_thai'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Thêm nhân sự thành công',
            'data' => [
                'ma_nhan_su' => $nhanSu->ma_nhan_su,
                'ho_ten' => $nhanSu->ho_ten,
                'vai_tro' => $nhanSu->vai_tro,
            ]
        ], 201);
    }

    /**
     * Cập nhật nhân sự
     */
    public function update(Request $request, $maNhanSu): JsonResponse
    {
        $nhanSu = NhanSu::find($maNhanSu);
        if (!$nhanSu) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy nhân sự'], 404);
        }

        $request->validate([
            'ho_ten' => 'sometimes|required|string|max:255',
            'so_dien_thoai' => 'sometimes|required|string|max:20',
            'vai_tro' => 'sometimes|required|string|in:quan_ly,nhan_vien',
            'trang_thai' => 'sometimes|required|string',
        ]);

        $nhanSu->update($request->only(['ho_ten', 'so_dien_thoai', 'vai_tro', 'trang_thai']));

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật nhân sự thành công',
            'data' => $nhanSu
        ]);
    }

    /**
     * Xóa nhân sự
     */
    public function destroy($maNhanSu): JsonResponse
    {
        $nhanSu = NhanSu::find($maNhanSu);
        if (!$nhanSu) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy nhân sự'], 404);
        }

        // Không cho xóa chính mình
        if (request()->user() && request()->user()->ma_nhan_su === $maNhanSu) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa tài khoản của chính bạn.'
            ], 400);
        }

        if ($nhanSu->donHangs()->count() > 0 || $nhanSu->caLams()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa nhân sự đã có dữ liệu hoạt động. Hãy khóa tài khoản thay vì xóa.'
            ], 400);
        }

        $nhanSu->tokens()->delete();
        $nhanSu->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa nhân sự thành công'
        ]);
    }

    /**
     * Reset mật khẩu
     */
    public function resetPassword(Request $request, $maNhanSu): JsonResponse
    {
        $nhanSu = NhanSu::find($maNhanSu);
        if (!$nhanSu) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy nhân sự'], 404);
        }

        $request->validate([
            'mat_khau_moi' => 'required|string|min:6',
        ]);

        $nhanSu->update([
            'mat_khau' => Hash::make($request->input('mat_khau_moi')),
        ]);

        // Xóa tất cả token cũ để buộc đăng nhập lại
        $nhanSu->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đặt lại mật khẩu thành công cho ' . $nhanSu->ho_ten,
        ]);
    }
}
