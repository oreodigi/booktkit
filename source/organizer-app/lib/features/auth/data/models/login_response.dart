/// Login Response Model
/// Login Response Model
class LoginResponse {
  final String status;
  final Organizer organizer;
  final String token;

  LoginResponse({
    required this.status,
    required this.organizer,
    required this.token,
  });

  factory LoginResponse.fromJson(Map<String, dynamic> json) {
    return LoginResponse(
      status: json['status'] ?? '',
      organizer: Organizer.fromJson(json['organizer'] ?? {}),
      token: json['token'] ?? '',
    );
  }

  Map<String, dynamic> toJson() {
    return {'status': status, 'organizer': organizer.toJson(), 'token': token};
  }
}

/// Organizer Model
class Organizer {
  final int id;
  final String photo;
  final String email;
  final String phone;
  final String username;
  final String password;
  final String status;
  final String amount;
  final String? emailVerifiedAt;
  final String facebook;
  final String twitter;
  final String linkedin;
  final String createdAt;
  final String updatedAt;
  final String themeVersion;

  Organizer({
    required this.id,
    required this.photo,
    required this.email,
    required this.phone,
    required this.username,
    required this.password,
    required this.status,
    required this.amount,
    this.emailVerifiedAt,
    required this.facebook,
    required this.twitter,
    required this.linkedin,
    required this.createdAt,
    required this.updatedAt,
    required this.themeVersion,
  });

  factory Organizer.fromJson(Map<String, dynamic> json) {
    return Organizer(
      id: json['id'] ?? 0,
      photo: json['photo'] ?? '',
      email: json['email'] ?? '',
      phone: json['phone'] ?? '',
      username: json['username'] ?? '',
      password: json['password'] ?? '',
      status: json['status']?.toString() ?? '0',
      amount: json['amount']?.toString() ?? '0.00',
      emailVerifiedAt: json['email_verified_at'],
      facebook: json['facebook'] ?? '',
      twitter: json['twitter'] ?? '',
      linkedin: json['linkedin'] ?? '',
      createdAt: json['created_at'] ?? '',
      updatedAt: json['updated_at'] ?? '',
      themeVersion: json['theme_version'] ?? 'light',
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'photo': photo,
      'email': email,
      'phone': phone,
      'username': username,
      'password': password,
      'status': status,
      'amount': amount,
      'email_verified_at': emailVerifiedAt,
      'facebook': facebook,
      'twitter': twitter,
      'linkedin': linkedin,
      'created_at': createdAt,
      'updated_at': updatedAt,
      'theme_version': themeVersion,
    };
  }
}
