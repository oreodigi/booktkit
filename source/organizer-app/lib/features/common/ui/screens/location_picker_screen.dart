import 'dart:async';
import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:geocoding/geocoding.dart';
import 'package:geolocator/geolocator.dart';
import 'package:latlong2/latlong.dart';

// ─────────────────────────────────────────────────────────────
// Result data class returned to the caller
// ─────────────────────────────────────────────────────────────

class LocationPickResult {
  final double lat;
  final double lon;
  final String address;

  const LocationPickResult({
    required this.lat,
    required this.lon,
    required this.address,
  });
}

// ─────────────────────────────────────────────────────────────
// Nominatim suggestion model
// ─────────────────────────────────────────────────────────────

class _Suggestion {
  final String displayName;
  final double lat;
  final double lon;

  const _Suggestion({
    required this.displayName,
    required this.lat,
    required this.lon,
  });

  /// Parses a single Photon GeoJSON feature
  factory _Suggestion.fromPhoton(Map<String, dynamic> feature) {
    final coords = (feature['geometry']['coordinates'] as List);
    final props = feature['properties'] as Map<String, dynamic>;

    // Build a readable label from available fields
    final parts = [
      props['name'],
      props['street'],
      props['city'] ?? props['town'] ?? props['village'],
      props['state'],
      props['country'],
    ].where((s) => s != null && (s as String).isNotEmpty).join(', ');

    return _Suggestion(
      displayName: parts.isNotEmpty ? parts : (props['display_name'] ?? 'Unknown'),
      lat: (coords[1] as num).toDouble(),
      lon: (coords[0] as num).toDouble(),
    );
  }
}

// ─────────────────────────────────────────────────────────────
// Screen
// ─────────────────────────────────────────────────────────────

class LocationPickerScreen extends StatefulWidget {
  final LatLng? initialPosition;

  const LocationPickerScreen({super.key, this.initialPosition});

  @override
  State<LocationPickerScreen> createState() => _LocationPickerScreenState();
}

class _LocationPickerScreenState extends State<LocationPickerScreen> {
  static const LatLng _defaultPosition = LatLng(23.8103, 90.4125); // Dhaka

  late final MapController _mapController;
  late LatLng _pinPosition;

  String _resolvedAddress = 'Move the map or tap to select location';
  bool _geocoding = false;
  bool _gpsLoading = false;
  bool _isDragging = false;

  // Debounce timers
  Timer? _debounceTimer;
  Timer? _suggestionTimer;

  // Search suggestions
  final TextEditingController _searchController = TextEditingController();
  final FocusNode _searchFocus = FocusNode();
  List<_Suggestion> _suggestions = [];
  bool _loadingSuggestions = false;

  final Dio _dio = Dio();

  @override
  void initState() {
    super.initState();
    _mapController = MapController();
    _pinPosition = widget.initialPosition ?? _defaultPosition;

    if (widget.initialPosition != null) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        _reverseGeocode(_pinPosition);
      });
    }

    _searchController.addListener(_onSearchChanged);
  }

  @override
  void dispose() {
    _debounceTimer?.cancel();
    _suggestionTimer?.cancel();
    _searchController.removeListener(_onSearchChanged);
    _searchController.dispose();
    _searchFocus.dispose();
    _mapController.dispose();
    _dio.close();
    super.dispose();
  }

  // ── Search suggestion logic ────────────────────────────────

  void _onSearchChanged() {
    final query = _searchController.text.trim();
    if (query.length < 3) {
      _suggestionTimer?.cancel();
      setState(() => _suggestions = []);
      return;
    }
    _suggestionTimer?.cancel();
    _suggestionTimer = Timer(const Duration(milliseconds: 500), () {
      _fetchSuggestions(query);
    });
  }

  Future<void> _fetchSuggestions(String query) async {
    setState(() => _loadingSuggestions = true);
    try {
      final response = await _dio.get<Map<String, dynamic>>(
        'https://photon.komoot.io/api/',
        queryParameters: {
          'q': query,
          'limit': 5,
          'lang': 'en',
        },
        options: Options(
          sendTimeout: const Duration(seconds: 6),
          receiveTimeout: const Duration(seconds: 6),
        ),
      );
      if (response.data != null && mounted) {
        final features =
            (response.data!['features'] as List?) ?? [];
        setState(() {
          _suggestions = features
              .map((e) => _Suggestion.fromPhoton(
                    e as Map<String, dynamic>,
                  ))
              .toList();
        });
      }
    } catch (_) {
      if (mounted) setState(() => _suggestions = []);
    } finally {
      if (mounted) setState(() => _loadingSuggestions = false);
    }
  }

  void _pickSuggestion(_Suggestion s) {
    final point = LatLng(s.lat, s.lon);
    _searchController.text = s.displayName;
    _searchFocus.unfocus();
    setState(() {
      _suggestions = [];
      _pinPosition = point;
      _resolvedAddress = s.displayName;
    });
    _mapController.move(point, 15);
  }

  // ── Geocoding helpers ──────────────────────────────────────

  Future<void> _reverseGeocode(LatLng point) async {
    setState(() {
      _geocoding = true;
      _resolvedAddress = 'Resolving address…';
    });
    try {
      final placemarks = await placemarkFromCoordinates(
        point.latitude,
        point.longitude,
      );
      if (placemarks.isNotEmpty) {
        final p = placemarks.first;
        final seen = <String>{};
        final parts = [
          p.name,
          p.street,
          p.subLocality,
          p.locality,
          p.subAdministrativeArea,
          p.administrativeArea,
          p.country,
        ].where((s) {
          if (s == null || s.isEmpty) return false;
          return seen.add(s);
        }).join(', ');
        setState(() => _resolvedAddress = parts.isEmpty
            ? '${point.latitude.toStringAsFixed(5)}, ${point.longitude.toStringAsFixed(5)}'
            : parts);
      } else {
        setState(() => _resolvedAddress =
            '${point.latitude.toStringAsFixed(5)}, ${point.longitude.toStringAsFixed(5)}');
      }
    } catch (_) {
      setState(() => _resolvedAddress =
          '${point.latitude.toStringAsFixed(5)}, ${point.longitude.toStringAsFixed(5)}');
    } finally {
      if (mounted) setState(() => _geocoding = false);
    }
  }

  // ── Map callbacks ──────────────────────────────────────────

  void _onMapTap(TapPosition tapPos, LatLng point) {
    _searchFocus.unfocus();
    setState(() {
      _pinPosition = point;
      _suggestions = [];
    });
    _reverseGeocode(point);
  }

  void _onPositionChanged(MapCamera camera, bool hasGesture) {
    setState(() {
      _pinPosition = camera.center;
      if (hasGesture) {
        _isDragging = true;
        _resolvedAddress = 'Move map to select location…';
      }
    });
    _debounceTimer?.cancel();
    _debounceTimer = Timer(const Duration(milliseconds: 700), () {
      if (mounted) {
        setState(() => _isDragging = false);
        _reverseGeocode(camera.center);
      }
    });
  }

  // ── GPS ────────────────────────────────────────────────────

  Future<void> _goToMyLocation() async {
    setState(() => _gpsLoading = true);
    try {
      bool serviceEnabled = await Geolocator.isLocationServiceEnabled();
      if (!serviceEnabled) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Location services are disabled.')),
          );
        }
        return;
      }
      LocationPermission permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
        if (permission == LocationPermission.denied) {
          if (mounted) {
            ScaffoldMessenger.of(context).showSnackBar(
              const SnackBar(content: Text('Location permission denied.')),
            );
          }
          return;
        }
      }
      if (permission == LocationPermission.deniedForever) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Permission permanently denied. Enable in Settings.'),
            ),
          );
        }
        return;
      }
      final pos = await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(accuracy: LocationAccuracy.high),
      );
      final newPoint = LatLng(pos.latitude, pos.longitude);
      setState(() => _pinPosition = newPoint);
      _mapController.move(newPoint, 16);
      await _reverseGeocode(newPoint);
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Could not get location: $e')),
        );
      }
    } finally {
      if (mounted) setState(() => _gpsLoading = false);
    }
  }

  // ── Confirm ────────────────────────────────────────────────

  void _confirm() {
    final isCoordOnly = RegExp(
      r'^-?\d+\.\d+,\s*-?\d+\.\d+$',
    ).hasMatch(_resolvedAddress);
    final addr = (_resolvedAddress == 'Resolving address…' ||
            _resolvedAddress == 'Move the map or tap to select location' ||
            _resolvedAddress == 'Move map to select location…' ||
            isCoordOnly)
        ? ''
        : _resolvedAddress;
    Navigator.of(context).pop(
      LocationPickResult(
        lat: _pinPosition.latitude,
        lon: _pinPosition.longitude,
        address: addr,
      ),
    );
  }

  // ─────────────────────────────────────────────────────────
  // Build
  // ─────────────────────────────────────────────────────────

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;
    final hasSuggestions = _suggestions.isNotEmpty || _loadingSuggestions;

    return Scaffold(
      body: Stack(
        children: [
          // ── Map ──────────────────────────────────────────
          FlutterMap(
            mapController: _mapController,
            options: MapOptions(
              initialCenter: _pinPosition,
              initialZoom: 13,
              onTap: _onMapTap,
              onPositionChanged: _onPositionChanged,
            ),
            children: [
              TileLayer(
                urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                userAgentPackageName: 'com.booktkit.organizer',
              ),
            ],
          ),

          // ── Centre pin ───────────────────────────────────
          IgnorePointer(
            child: Center(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  AnimatedScale(
                    scale: _isDragging ? 1.3 : 1.0,
                    duration: const Duration(milliseconds: 150),
                    child: Icon(
                      Icons.location_pin,
                      color: _isDragging ? Colors.orange : Colors.red,
                      size: 48,
                      shadows: const [
                        Shadow(
                          color: Colors.black38,
                          blurRadius: 8,
                          offset: Offset(0, 2),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 24),
                ],
              ),
            ),
          ),

          // ── Top search bar + suggestions ─────────────────
          SafeArea(
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Row(
                    children: [
                      _MapButton(
                        onTap: () => Navigator.of(context).pop(),
                        child: const Icon(Icons.arrow_back),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Container(
                          height: 48,
                          decoration: BoxDecoration(
                            color: isDark ? Colors.grey.shade900 : Colors.white,
                            borderRadius: BorderRadius.circular(12),
                            boxShadow: const [
                              BoxShadow(
                                color: Colors.black26,
                                blurRadius: 8,
                                offset: Offset(0, 2),
                              ),
                            ],
                          ),
                          child: TextField(
                            controller: _searchController,
                            focusNode: _searchFocus,
                            textInputAction: TextInputAction.search,
                            onSubmitted: (_) {
                              if (_suggestions.isNotEmpty) {
                                _pickSuggestion(_suggestions.first);
                              }
                            },
                            decoration: InputDecoration(
                              hintText: 'Search address…',
                              border: InputBorder.none,
                              prefixIcon: const Icon(Icons.search, size: 20),
                              suffixIcon: _loadingSuggestions
                                  ? const Padding(
                                      padding: EdgeInsets.all(12),
                                      child: SizedBox(
                                        width: 18,
                                        height: 18,
                                        child: CircularProgressIndicator(
                                            strokeWidth: 2),
                                      ),
                                    )
                                  : _searchController.text.isNotEmpty
                                      ? IconButton(
                                          icon: const Icon(Icons.clear, size: 18),
                                          onPressed: () {
                                            _searchController.clear();
                                            setState(() => _suggestions = []);
                                          },
                                        )
                                      : null,
                              contentPadding:
                                  const EdgeInsets.symmetric(vertical: 14),
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),

                  // ── Suggestions dropdown ──────────────────
                  if (hasSuggestions)
                    Container(
                      margin: const EdgeInsets.only(left: 56, top: 4),
                      decoration: BoxDecoration(
                        color: isDark ? Colors.grey.shade900 : Colors.white,
                        borderRadius: BorderRadius.circular(12),
                        boxShadow: const [
                          BoxShadow(
                            color: Colors.black26,
                            blurRadius: 8,
                            offset: Offset(0, 2),
                          ),
                        ],
                      ),
                      child: ClipRRect(
                        borderRadius: BorderRadius.circular(12),
                        child: _loadingSuggestions && _suggestions.isEmpty
                            ? const Padding(
                                padding: EdgeInsets.all(16),
                                child: Center(
                                  child: SizedBox(
                                    width: 20,
                                    height: 20,
                                    child: CircularProgressIndicator(
                                        strokeWidth: 2),
                                  ),
                                ),
                              )
                            : ListView.separated(
                                padding: EdgeInsets.zero,
                                shrinkWrap: true,
                                itemCount: _suggestions.length,
                                separatorBuilder: (context, _) =>
                                    const Divider(height: 1),
                                itemBuilder: (_, i) {
                                  final s = _suggestions[i];
                                  return ListTile(
                                    dense: true,
                                    leading: const Icon(
                                      Icons.location_on_outlined,
                                      color: Colors.teal,
                                      size: 20,
                                    ),
                                    title: Text(
                                      s.displayName,
                                      maxLines: 2,
                                      overflow: TextOverflow.ellipsis,
                                      style: theme.textTheme.bodySmall,
                                    ),
                                    onTap: () => _pickSuggestion(s),
                                  );
                                },
                              ),
                      ),
                    ),
                ],
              ),
            ),
          ),

          // ── Bottom info + confirm ─────────────────────────
          Positioned(
            left: 0,
            right: 0,
            bottom: 0,
            child: Container(
              decoration: BoxDecoration(
                color: isDark ? Colors.grey.shade900 : Colors.white,
                borderRadius:
                    const BorderRadius.vertical(top: Radius.circular(20)),
                boxShadow: const [
                  BoxShadow(
                      color: Colors.black26,
                      blurRadius: 12,
                      offset: Offset(0, -2)),
                ],
              ),
              padding: EdgeInsets.fromLTRB(
                20,
                16,
                20,
                MediaQuery.of(context).padding.bottom + 16,
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Center(
                    child: Container(
                      width: 40,
                      height: 4,
                      decoration: BoxDecoration(
                        color: Colors.grey.shade400,
                        borderRadius: BorderRadius.circular(2),
                      ),
                    ),
                  ),
                  const SizedBox(height: 14),
                  Row(
                    children: [
                      const Icon(Icons.location_on_rounded,
                          color: Colors.teal, size: 20),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          _geocoding
                              ? 'Resolving address…'
                              : _resolvedAddress,
                          style: theme.textTheme.bodyMedium
                              ?.copyWith(fontWeight: FontWeight.w500),
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 4),
                  Text(
                    '${_pinPosition.latitude.toStringAsFixed(6)}, '
                    '${_pinPosition.longitude.toStringAsFixed(6)}',
                    style: theme.textTheme.bodySmall?.copyWith(
                      color: Colors.grey,
                      fontFamily: 'monospace',
                    ),
                  ),
                  const SizedBox(height: 14),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton.icon(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: Colors.teal,
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12),
                        ),
                        elevation: 0,
                      ),
                      icon: const Icon(Icons.check_circle_outline),
                      label: const Text(
                        'Confirm Location',
                        style: TextStyle(
                            fontSize: 16, fontWeight: FontWeight.w600),
                      ),
                      onPressed: _geocoding ? null : _confirm,
                    ),
                  ),
                ],
              ),
            ),
          ),

          // ── My Location FAB ───────────────────────────────
          Positioned(
            right: 16,
            bottom: 210,
            child: FloatingActionButton(
              heroTag: 'myLocation',
              backgroundColor:
                  isDark ? Colors.grey.shade800 : Colors.white,
              foregroundColor: Colors.teal,
              elevation: 4,
              onPressed: _gpsLoading ? null : _goToMyLocation,
              tooltip: 'My Location',
              child: _gpsLoading
                  ? const SizedBox(
                      width: 22,
                      height: 22,
                      child: CircularProgressIndicator(
                          strokeWidth: 2.5, color: Colors.teal),
                    )
                  : const Icon(Icons.my_location_rounded),
            ),
          ),
        ],
      ),
    );
  }
}

// ─────────────────────────────────────────────────────────────
// Map overlay button helper
// ─────────────────────────────────────────────────────────────

class _MapButton extends StatelessWidget {
  final Widget child;
  final VoidCallback onTap;

  const _MapButton({required this.child, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Theme.of(context).brightness == Brightness.dark
          ? Colors.grey.shade900
          : Colors.white,
      borderRadius: BorderRadius.circular(12),
      elevation: 4,
      shadowColor: Colors.black26,
      child: InkWell(
        borderRadius: BorderRadius.circular(12),
        onTap: onTap,
        child: SizedBox(
          width: 48,
          height: 48,
          child: Center(child: child),
        ),
      ),
    );
  }
}
