/// Signup Request Model
class SignupRequest {
  final String username;
  final String email;
  final String password;
  final String passwordConfirmation;
  final String name;

  SignupRequest({
    required this.username,
    required this.email,
    required this.password,
    required this.passwordConfirmation,
    required this.name,
  });

  Map<String, dynamic> toJson() {
    return {
      'username': username,
      'email': email,
      'password': password,
      'password_confirmation': passwordConfirmation,
      'name': name,
    };
  }
}
