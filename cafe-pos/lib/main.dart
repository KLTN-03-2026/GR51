import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'views/viewmodels/auth_viewmodel.dart';
import 'views/viewmodels/menu_viewmodel.dart';
import 'views/viewmodels/cart_viewmodel.dart';
import 'views/viewmodels/table_selection_viewmodel.dart';
import 'views/viewmodels/order_viewmodel.dart';
import 'views/viewmodels/inventory_viewmodel.dart';
import 'views/main_layout.dart';
import 'views/auth/login_view.dart';

import 'package:google_fonts/google_fonts.dart';

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
      title: 'GUNPLA COFFE POS',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        scaffoldBackgroundColor: const Color(0xFFFDFBF7),
        colorScheme: ColorScheme.fromSeed(
          seedColor: const Color(0xFF6E4423), // Primary warm brown
          primary: const Color(0xFF6E4423),
          surface: Colors.white,
          background: const Color(0xFFFDFBF7),
        ),
        textTheme: GoogleFonts.outfitTextTheme(
          Theme.of(context).textTheme,
        ).apply(
          bodyColor: const Color(0xFF333333),
          displayColor: const Color(0xFF4A2D17),
        ),
        appBarTheme: const AppBarTheme(
          backgroundColor: Colors.white,
          foregroundColor: Color(0xFF4A2D17),
          elevation: 0,
        ),
        cardColor: Colors.white,
        useMaterial3: true,
      ),
      home: hasToken ? const MainLayout() : const LoginView(),
    );
  }
}
