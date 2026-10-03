class CategoryItem {
  final int id;
  final String name;
  final String slug;

  CategoryItem({required this.id, required this.name, required this.slug});

  factory CategoryItem.fromJson(Map<String, dynamic> json) {
    return CategoryItem(
      id: json['id'] as int? ?? 0,
      name: json['name']?.toString() ?? '',
      slug: json['slug']?.toString() ?? '',
    );
  }
}

class CategoriesResponse {
  final List<CategoryItem> categories;

  CategoriesResponse({required this.categories});

  factory CategoriesResponse.fromJson(Map<String, dynamic> json) {
    final data = json['data'] as Map<String, dynamic>? ?? {};
    final list = data['categories'] as List<dynamic>? ?? [];
    return CategoriesResponse(
      categories: list
          .map((e) => CategoryItem.fromJson(e as Map<String, dynamic>))
          .toList(),
    );
  }
}
