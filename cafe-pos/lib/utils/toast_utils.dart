import 'package:flutter/material.dart';

class ToastUtils {
  static void showSuccess(BuildContext context, String message) {
    _showToast(context, message, Colors.green, Icons.check_circle);
  }

  static void showError(BuildContext context, String message) {
    _showToast(context, message, Colors.redAccent, Icons.error);
  }

  static void showWarning(BuildContext context, String message) {
    _showToast(context, message, Colors.orangeAccent, Icons.warning);
  }

  static void showInfo(BuildContext context, String message) {
    _showToast(context, message, Colors.blueAccent, Icons.info);
  }

  static void _showToast(BuildContext context, String message, Color color, IconData icon) {
    ScaffoldMessenger.of(context).removeCurrentSnackBar();
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Row(
          children: [
            Icon(icon, color: Colors.white),
            const SizedBox(width: 12),
            Expanded(
              child: Text(
                message,
                style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w500),
              ),
            ),
          ],
        ),
        backgroundColor: color.withOpacity(0.9),
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        margin: const EdgeInsets.symmetric(horizontal: 24, vertical: 24),
        duration: const Duration(seconds: 3),
        elevation: 4,
      ),
    );
  }
}
