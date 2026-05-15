import 'package:pdf/pdf.dart';
import 'package:pdf/widgets.dart' as pw;
import 'package:printing/printing.dart';
import 'package:intl/intl.dart';
import '../models/cart_item_model.dart';

class InvoicePrinter {
  static Future<void> printInvoice({
    required List<CartItem> items,
    required double totalPrice,
    required double customerGiven,
    required double changeAmount,
    required String loaiDon,
    required String paymentMethod,
    int? banId,
    bool isProvisional = false,
  }) async {
    final pdf = pw.Document();
    
    // Load font for Vietnamese
    final font = await PdfGoogleFonts.robotoRegular();
    final fontBold = await PdfGoogleFonts.robotoBold();
    
    final currencyFormat = NumberFormat.currency(locale: 'vi_VN', symbol: 'đ');
    final dateFormat = DateFormat('dd/MM/yyyy HH:mm');

    pdf.addPage(
      pw.Page(
        pageFormat: PdfPageFormat.roll80,
        margin: const pw.EdgeInsets.all(16),
        build: (pw.Context context) {
          return pw.Column(
            crossAxisAlignment: pw.CrossAxisAlignment.center,
            children: [
              pw.Text('GUNPLA COFFEE', style: pw.TextStyle(font: fontBold, fontSize: 18)),
              pw.SizedBox(height: 4),
              pw.Text('Địa chỉ: 36 Đường Điện Biên Ph, Thành phố Đà Nẵng', style: pw.TextStyle(font: font, fontSize: 10), textAlign: pw.TextAlign.center),
              pw.Text('Điện thoại: 0944799214', style: pw.TextStyle(font: font, fontSize: 10), textAlign: pw.TextAlign.center),
              pw.SizedBox(height: 12),
              pw.Divider(borderStyle: pw.BorderStyle.dashed),
              pw.SizedBox(height: 8),
              
              pw.Text(
                isProvisional ? 'PHIẾU TẠM TÍNH' : 'HÓA ĐƠN THANH TOÁN', 
                style: pw.TextStyle(font: fontBold, fontSize: 16)
              ),
              pw.SizedBox(height: 12),
              
              // Order info
              pw.Row(
                mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
                children: [
                  pw.Text('Ngày: ${dateFormat.format(DateTime.now())}', style: pw.TextStyle(font: font, fontSize: 10)),
                ]
              ),
              pw.Row(
                mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
                children: [
                  pw.Text('Loại đơn: ${loaiDon == 'mang_di' ? 'Mang đi' : 'Tại bàn'}', style: pw.TextStyle(font: font, fontSize: 10)),
                  if (banId != null)
                    pw.Text('Bàn: $banId', style: pw.TextStyle(font: fontBold, fontSize: 10)),
                ]
              ),
              pw.SizedBox(height: 8),
              pw.Divider(borderStyle: pw.BorderStyle.dashed),
              pw.SizedBox(height: 8),

              // Headers
              pw.Row(
                children: [
                  pw.Expanded(flex: 3, child: pw.Text('Món', style: pw.TextStyle(font: fontBold, fontSize: 10))),
                  pw.Expanded(flex: 1, child: pw.Text('SL', style: pw.TextStyle(font: fontBold, fontSize: 10), textAlign: pw.TextAlign.center)),
                  pw.Expanded(flex: 2, child: pw.Text('T.Tiền', style: pw.TextStyle(font: fontBold, fontSize: 10), textAlign: pw.TextAlign.right)),
                ]
              ),
              pw.SizedBox(height: 4),
              pw.Divider(),
              pw.SizedBox(height: 4),

              // Items
              ...items.map((item) {
                return pw.Column(
                  crossAxisAlignment: pw.CrossAxisAlignment.start,
                  children: [
                    pw.Row(
                      crossAxisAlignment: pw.CrossAxisAlignment.start,
                      children: [
                        pw.Expanded(
                          flex: 3, 
                          child: pw.Column(
                            crossAxisAlignment: pw.CrossAxisAlignment.start,
                            children: [
                              pw.Text(item.mon.tenMon, style: pw.TextStyle(font: fontBold, fontSize: 10)),
                              if (item.selectedSize != null)
                                pw.Text('Size: ${item.selectedSize!['ten_kich_co']}', style: pw.TextStyle(font: font, fontSize: 9, color: PdfColors.grey700)),
                              ...item.selectedToppings.map((t) => pw.Text('+ ${t['ten_topping']}', style: pw.TextStyle(font: font, fontSize: 9, color: PdfColors.grey700))),
                              if (item.ghiChu != null && item.ghiChu!.isNotEmpty)
                                pw.Text('Note: ${item.ghiChu}', style: pw.TextStyle(font: font, fontSize: 9, color: PdfColors.grey700)),
                            ]
                          )
                        ),
                        pw.Expanded(
                          flex: 1, 
                          child: pw.Text('${item.soLuong}', style: pw.TextStyle(font: font, fontSize: 10), textAlign: pw.TextAlign.center)
                        ),
                        pw.Expanded(
                          flex: 2, 
                          child: pw.Text(currencyFormat.format(item.thanhTien), style: pw.TextStyle(font: font, fontSize: 10), textAlign: pw.TextAlign.right)
                        ),
                      ]
                    ),
                    pw.SizedBox(height: 4),
                  ]
                );
              }),
              
              pw.Divider(borderStyle: pw.BorderStyle.dashed),
              pw.SizedBox(height: 4),

              // Totals
              pw.Row(
                mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
                children: [
                  pw.Text('TỔNG CỘNG:', style: pw.TextStyle(font: fontBold, fontSize: 12)),
                  pw.Text(currencyFormat.format(totalPrice), style: pw.TextStyle(font: fontBold, fontSize: 12)),
                ]
              ),
              
              if (!isProvisional) ...[
                pw.SizedBox(height: 4),
                pw.Row(
                  mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
                  children: [
                    pw.Text('Phương thức:', style: pw.TextStyle(font: font, fontSize: 10)),
                    pw.Text(paymentMethod, style: pw.TextStyle(font: font, fontSize: 10)),
                  ]
                ),
                if (paymentMethod == 'Tiền mặt') ...[
                  pw.SizedBox(height: 2),
                  pw.Row(
                    mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
                    children: [
                      pw.Text('Khách đưa:', style: pw.TextStyle(font: font, fontSize: 10)),
                      pw.Text(currencyFormat.format(customerGiven), style: pw.TextStyle(font: font, fontSize: 10)),
                    ]
                  ),
                  pw.SizedBox(height: 2),
                  pw.Row(
                    mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
                    children: [
                      pw.Text('Tiền thừa:', style: pw.TextStyle(font: font, fontSize: 10)),
                      pw.Text(currencyFormat.format(changeAmount), style: pw.TextStyle(font: font, fontSize: 10)),
                    ]
                  ),
                ]
              ],

              pw.SizedBox(height: 16),
              pw.Divider(borderStyle: pw.BorderStyle.dashed),
              pw.SizedBox(height: 8),
              pw.Text('Cảm ơn Quý khách & Hẹn gặp lại!', style: pw.TextStyle(font: font, fontSize: 10), textAlign: pw.TextAlign.center),
              pw.SizedBox(height: 4),
              pw.Text('Powered by Antigravity', style: pw.TextStyle(font: font, fontSize: 8, color: PdfColors.grey600), textAlign: pw.TextAlign.center),
            ],
          );
        },
      ),
    );

    // Call the print dialog
    await Printing.layoutPdf(
      onLayout: (PdfPageFormat format) async => pdf.save(),
      name: isProvisional ? 'phieu_tam_tinh.pdf' : 'hoa_don.pdf',
    );
  }
}
