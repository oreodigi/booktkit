class IncomeModel {
  bool? success;
  Data? data;

  IncomeModel({this.success, this.data});

  IncomeModel.fromJson(Map<String, dynamic> json) {
    success = json['success'];
    data = json['data'] != null ? Data.fromJson(json['data']) : null;
  }

  Map<String, dynamic> toJson() {
    final Map<String, dynamic> data = <String, dynamic>{};
    data['success'] = success;
    if (this.data != null) {
      data['data'] = this.data!.toJson();
    }
    return data;
  }
}

class Data {
  List<String>? months;
  List<double>? incomes;
  List<double>? rejects;
  List<double>? commissions;
  List<double>? expenses;

  Data({
    this.months,
    this.incomes,
    this.rejects,
    this.commissions,
    this.expenses,
  });

  Data.fromJson(Map<String, dynamic> json) {
    months = List<String>.from(json['months'] ?? []);

    incomes = (json['incomes'] as List?)
        ?.map((e) => double.tryParse(e.toString()) ?? 0.0)
        .toList();

    rejects = (json['rejects'] as List?)
        ?.map((e) => double.tryParse(e.toString()) ?? 0.0)
        .toList();

    commissions = (json['commissions'] as List?)
        ?.map((e) => double.tryParse(e.toString()) ?? 0.0)
        .toList();

    expenses = (json['expenses'] as List?)
        ?.map((e) => double.tryParse(e.toString()) ?? 0.0)
        .toList();
  }

  Map<String, dynamic> toJson() {
    return {
      'months': months,
      'incomes': incomes,
      'rejects': rejects,
      'commissions': commissions,
      'expenses': expenses,
    };
  }
}
