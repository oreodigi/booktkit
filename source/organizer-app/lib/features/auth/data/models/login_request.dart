/// Login Request Model
class LoginRequest {
  final String username;
  final String password;
  final String deviceName;

  LoginRequest({
    required this.username,
    required this.password,
    this.deviceName = 'mobile',
  });

  Map<String, dynamic> toJson() {
    return {
      'username': username,
      'password': password,
      'device_name': deviceName,
    };
  }
}
