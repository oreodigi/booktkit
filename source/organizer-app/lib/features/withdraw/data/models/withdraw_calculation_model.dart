class WithdrawCalculationModel {
  final double totalCharge;
  final double receiveBalance;
  final double userBalance;

  WithdrawCalculationModel({
    required this.totalCharge,
    required this.receiveBalance,
    required this.userBalance,
  });

  factory WithdrawCalculationModel.fromJson(Map<String, dynamic> json) {
    return WithdrawCalculationModel(
      totalCharge: double.tryParse(json['total_charge']?.toString() ?? '0') ?? 0.0,
      receiveBalance: double.tryParse(json['receive_balance']?.toString() ?? '0') ?? 0.0,
      userBalance: double.tryParse(json['user_balance']?.toString() ?? '0') ?? 0.0,
    );
  }
}
