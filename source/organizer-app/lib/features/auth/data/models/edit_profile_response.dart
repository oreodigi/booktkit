/// Edit Profile Response Model
class EditProfileResponse {
  final bool success;
  final EditProfileData data;

  EditProfileResponse({required this.success, required this.data});

  factory EditProfileResponse.fromJson(Map<String, dynamic> json) {
    return EditProfileResponse(
      success: json['success'] ?? false,
      data: EditProfileData.fromJson(json['data'] ?? {}),
    );
  }

  Map<String, dynamic> toJson() {
    return {'success': success, 'data': data.toJson()};
  }
}

class EditProfileData {
  final List<Language> languages;
  final Organizer organizer;
  final List<OrganizerInfo> organizerInfos;

  EditProfileData({
    required this.languages,
    required this.organizer,
    required this.organizerInfos,
  });

  factory EditProfileData.fromJson(Map<String, dynamic> json) {
    return EditProfileData(
      languages:
          (json['languages'] as List<dynamic>?)
              ?.map((e) => Language.fromJson(e as Map<String, dynamic>))
              .toList() ??
          [],
      organizer: Organizer.fromJson(json['organizer'] ?? {}),
      organizerInfos:
          (json['organizer_infos'] as List<dynamic>?)
              ?.map((e) => OrganizerInfo.fromJson(e as Map<String, dynamic>))
              .toList() ??
          [],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'languages': languages.map((e) => e.toJson()).toList(),
      'organizer': organizer.toJson(),
      'organizer_infos': organizerInfos.map((e) => e.toJson()).toList(),
    };
  }
}

class Language {
  final int id;
  final String name;
  final String code;
  final String direction;
  final String isDefault;
  final String createdAt;
  final String updatedAt;

  Language({
    required this.id,
    required this.name,
    required this.code,
    required this.direction,
    required this.isDefault,
    required this.createdAt,
    required this.updatedAt,
  });

  factory Language.fromJson(Map<String, dynamic> json) {
    return Language(
      id: json['id'] ?? 0,
      name: json['name'] ?? '',
      code: json['code'] ?? '',
      direction: json['direction']?.toString() ?? '0',
      isDefault: json['is_default']?.toString() ?? '0',
      createdAt: json['created_at'] ?? '',
      updatedAt: json['updated_at'] ?? '',
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'code': code,
      'direction': direction,
      'is_default': isDefault,
      'created_at': createdAt,
      'updated_at': updatedAt,
    };
  }
}

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
      amount: json['amount']?.toString() ?? '0',
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

class OrganizerInfo {
  final int id;
  final String languageId;
  final String organizerId;
  final String name;
  final String country;
  final String city;
  final String state;
  final String zipCode;
  final String address;
  final String? details;
  final String designation;
  final String createdAt;
  final String updatedAt;

  OrganizerInfo({
    required this.id,
    required this.languageId,
    required this.organizerId,
    required this.name,
    required this.country,
    required this.city,
    required this.state,
    required this.zipCode,
    required this.address,
    this.details,
    required this.designation,
    required this.createdAt,
    required this.updatedAt,
  });

  factory OrganizerInfo.fromJson(Map<String, dynamic> json) {
    return OrganizerInfo(
      id: json['id'] ?? 0,
      languageId: json['language_id']?.toString() ?? '',
      organizerId: json['organizer_id']?.toString() ?? '',
      name: json['name'] ?? '',
      country: json['country'] ?? '',
      city: json['city'] ?? '',
      state: json['state'] ?? '',
      zipCode: json['zip_code'] ?? '',
      address: json['address'] ?? '',
      details: json['details'],
      designation: json['designation'] ?? '',
      createdAt: json['created_at'] ?? '',
      updatedAt: json['updated_at'] ?? '',
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'language_id': languageId,
      'organizer_id': organizerId,
      'name': name,
      'country': country,
      'city': city,
      'state': state,
      'zip_code': zipCode,
      'address': address,
      'details': details,
      'designation': designation,
      'created_at': createdAt,
      'updated_at': updatedAt,
    };
  }
}
