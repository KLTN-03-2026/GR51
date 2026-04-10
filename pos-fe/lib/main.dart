import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'views/viewmodels/auth_viewmodel.dart';
import 'views/viewmodels/menu_viewmodel.dart';
import 'views/viewmodels/cart_viewmodel.dart';
import 'views/viewmodels/table_selection_viewmodel.dart';
import 'views/viewmodels/order_viewmodel.dart';
import 'views/viewmodels/kds_viewmodel.dart';
import 'views/viewmodels/inventory_viewmodel.dart';
import 'views/main_layout.dart';
import 'views/auth/login_view.dart';

final GlobalKey<NavigatorState> navigatorKey = GlobalKey<NavigatorState>();

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  final prefs = await SharedPreferences.getInstance();
  final hasToken = prefs.getString('auth_token') != null && prefs.getString('auth_token')!.isNotEmpty;

  runApp(
    MultiProvider(
      providers: [
        ChangeNotifierProvider(create: (_) => AuthViewModel()),
        ChangeNotifierProvider(create: (_) => MenuViewModel()),
        ChangeNotifierProvider(create: (_) => CartViewModel()),
        ChangeNotifierProvider(create: (_) => TableSelectionViewModel()),
        ChangeNotifierProvider(create: (_) => OrderViewModel()),
        ChangeNotifierProvider(create: (_) => KdsViewModel()),
        ChangeNotifierProvider(create: (_) => InventoryViewModel()),
      ],
      child: MyApp(hasToken: hasToken),
    ),
  );
}

class MyApp extends StatelessWidget {
  final bool hasToken;
  const MyApp({super.key, required this.hasToken});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      navigatorKey: navigatorKey,
      title: 'Smart Cafe POS',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(seedColor: Colors.orange),
        useMaterial3: true,
        fontFamily: 'Roboto',
      ),
      home: hasToken ? const MainLayout() : const LoginView(),
    );
  }
}
