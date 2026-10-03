/// Signup Response Model
class SignupResponse {
  final bool status;
  final String message;

  SignupResponse({required this.status, required this.message});

  factory SignupResponse.fromJson(Map<String, dynamic> json) {
    return SignupResponse(
      status: json['success'] ?? json['status'] ?? false,
      message: json['message'] ?? '',
    );
  }

  Map<String, dynamic> toJson() {
    return {'status': status, 'message': message};
  }
}
