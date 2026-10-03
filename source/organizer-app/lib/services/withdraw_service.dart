import 'package:dio/dio.dart';
import 'package:booktkit_organizer/app/urls.dart';
import 'package:booktkit_organizer/features/withdraw/data/models/method_input_model.dart';
import 'package:booktkit_organizer/features/withdraw/data/models/withdraw_calculation_model.dart';
import 'package:booktkit_organizer/features/withdraw/data/models/withdraws_model.dart';

class WithdrawService {
  final Dio dio;

  WithdrawService(this.dio);

  Future<WithdrawResponse> getWithdrawHistory() async {
    try {
      final response = await dio.get(Urls.orgWithdraw);

      return WithdrawResponse.fromJson(response.data);
    } on DioException catch (e) {
      String message = 'Something went wrong';

      if (e.response != null && e.response?.data != null) {
        message = e.response?.data['message']?.toString() ?? message;
      }

      throw Exception(message);
    }
  }

  Future<List<Method>> getWithdrawMethods() async {
    try {
      final response = await dio.get(Urls.withdrawCreate);
      final data = response.data as Map<String, dynamic>;
      final methods = (data['data']['methods'] as List<dynamic>? ?? [])
          .map((m) => Method.fromJson(m as Map<String, dynamic>))
          .toList();
      return methods;
    } on DioException catch (e) {
      String message = 'Something went wrong';
      if (e.response != null && e.response?.data != null) {
        message = e.response?.data['message']?.toString() ?? message;
      }
      throw Exception(message);
    }
  }

  Future<List<MethodInputField>> getMethodInputs(int methodId) async {
    try {
      final response =
          await dio.get('${Urls.getMethodInput}/$methodId');
      final data = response.data as Map<String, dynamic>;
      final list = data['data'] as List<dynamic>? ?? [];
      return list
          .map((f) =>
              MethodInputField.fromJson(f as Map<String, dynamic>))
          .toList()
        ..sort((a, b) => a.orderNumber.compareTo(b.orderNumber));
    } on DioException catch (e) {
      String message = 'Something went wrong';
      if (e.response != null && e.response?.data != null) {
        message = e.response?.data['message']?.toString() ?? message;
      }
      throw Exception(message);
    }
  }

  Future<WithdrawCalculationModel> calculateWithdrawal(int methodId, double amount) async {
    try {
      final response = await dio.get('${Urls.withdrawCalculation}/$methodId/$amount');
      return WithdrawCalculationModel.fromJson(response.data as Map<String, dynamic>);
    } on DioException catch (e) {
      String message = 'Calculation failed';
      if (e.response != null && e.response?.data != null) {
        message = e.response?.data['message']?.toString() ?? message;
      }
      throw Exception(message);
    }
  }

  Future<void> deleteWithdrawHistory(int id) async {
    try {
      final response = await dio.post(
        Urls.withdrawDelete,
        data: {'id': id},
      );
      if (response.data['success'] != true) {
        throw Exception(response.data['message']?.toString() ?? 'Failed to delete withdraw request');
      }
    } on DioException catch (e) {
      String message = 'Failed to delete withdraw request';
      if (e.response != null && e.response?.data != null) {
        message = e.response?.data['message']?.toString() ?? message;
      }
      throw Exception(message);
    }
  }

  Future<void> submitWithdrawRequest(FormData formData) async {
    try {
      final response = await dio.post(
        Urls.withdrawSubmit,
        data: formData,
      );
      // The API returns 'succcess': true (notice the typo in true condition in image but we check raw bool or true string)
      final isSuccess = response.data['succcess'] == true || response.data['success'] == true;
      if (!isSuccess) {
        throw Exception(response.data['message']?.toString() ?? 'Failed to submit withdrawal request');
      }
    } on DioException catch (e) {
      String message = 'Failed to submit withdrawal request';
      if (e.response != null && e.response?.data != null) {
        message = e.response?.data['message']?.toString() ?? message;
      }
      throw Exception(message);
    }
  }
}
