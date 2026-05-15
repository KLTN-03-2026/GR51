import 'package:flutter/services.dart';
import 'package:intl/intl.dart';

class CurrencyInputFormatter extends TextInputFormatter {
  @override
  TextEditingValue formatEditUpdate(
      TextEditingValue oldValue, TextEditingValue newValue) {
    
    // Nếu trống thì trả về giá trị trống an toàn
    if (newValue.text.isEmpty) {
      return newValue.copyWith(
        text: '',
        selection: const TextSelection.collapsed(offset: 0),
      );
    }

    // Chỉ lấy các chữ số
    String plainText = newValue.text.replaceAll(RegExp(r'[^0-9]'), '');
    
    if (plainText.isEmpty) {
      return const TextEditingValue(
        text: '',
        selection: TextSelection.collapsed(offset: 0),
      );
    }

    // Giới hạn độ dài để tránh lỗi tràn số (nếu cần)
    if (plainText.length > 15) {
      plainText = plainText.substring(0, 15);
    }

    // Định dạng số tiền
    final formatter = NumberFormat('#,###', 'vi_VN');
    String newText = formatter.format(double.parse(plainText));

    // Tính toán vị trí con trỏ mới
    // Chúng ta dựa trên số lượng chữ số nằm trước con trỏ cũ
    int oldSelectionIndex = newValue.selection.end;
    if (oldSelectionIndex < 0) oldSelectionIndex = 0;
    if (oldSelectionIndex > newValue.text.length) oldSelectionIndex = newValue.text.length;

    int digitsBeforeCursor = newValue.text
        .substring(0, oldSelectionIndex)
        .replaceAll(RegExp(r'[^0-9]'), '')
        .length;

    int newSelectionIndex = 0;
    int digitsFound = 0;
    
    // Tìm vị trí con trỏ trong chuỗi mới sao cho số lượng chữ số phía trước bằng với chuỗi cũ
    while (newSelectionIndex < newText.length && digitsFound < digitsBeforeCursor) {
      if (RegExp(r'[0-9]').hasMatch(newText[newSelectionIndex])) {
        digitsFound++;
      }
      newSelectionIndex++;
    }

    // Đảm bảo con trỏ không bao giờ nằm ở vị trí không hợp lệ (ví dụ: đứng sau dấu phẩy/chấm ở cuối chuỗi)
    return TextEditingValue(
      text: newText,
      selection: TextSelection.collapsed(offset: newSelectionIndex),
    );
  }
}
