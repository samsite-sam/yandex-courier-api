import { NextRequest, NextResponse } from "next/server";
import ZAI from "z-ai-web-dev-sdk";

// Поисковые запросы для изображений
const IMAGE_QUERIES = [
  "курьер яндекс еда доставка",
  "delivery courier on bike",
  "food delivery worker",
  "courier with delivery bag",
  "delivery person smartphone",
  "courier delivering food",
  "bike courier city",
  "delivery service worker"
];

interface ImageRequest {
  query?: string;
  articleTitle?: string;
}

interface SearchResult {
  url: string;
  name: string;
  snippet: string;
  host_name: string;
}

export async function POST(request: NextRequest) {
  try {
    const body: ImageRequest = await request.json().catch(() => ({}));

    const zai = await ZAI.create();

    // Формируем поисковый запрос
    const searchQuery = body.query ||
      body.articleTitle ||
      IMAGE_QUERIES[Math.floor(Math.random() * IMAGE_QUERIES.length)];

    // Ищем изображения через web search
    const searchResult = await zai.functions.invoke("web_search", {
      query: `${searchQuery} изображение фото`,
      num: 10
    });

    const results = searchResult as SearchResult[];

    // Фильтруем результаты, ищем прямые ссылки на изображения
    const imageUrls: string[] = [];

    for (const result of results || []) {
      // Проверяем, есть ли в результате прямая ссылка на изображение
      if (result.url && isImageUrl(result.url)) {
        imageUrls.push(result.url);
      }

      // Извлекаем URL изображений из snippet
      const snippetImages = extractImageUrls(result.snippet || "");
      imageUrls.push(...snippetImages);
    }

    // Если не нашли прямые ссылки, пробуем найти через бесплатные сервисы
    if (imageUrls.length === 0) {
      // Используем Unsplash Source (бесплатно, без API ключа)
      const unsplashKeywords = ["delivery", "courier", "bike", "food"];
      const keyword = unsplashKeywords[Math.floor(Math.random() * unsplashKeywords.length)];

      return NextResponse.json({
        success: true,
        image: {
          url: `https://source.unsplash.com/1200x800/?${keyword}`,
          alt: searchQuery,
          source: "unsplash",
          credit: "Unsplash",
          license: "Free to use"
        },
        fallback: true
      });
    }

    // Возвращаем лучшее найденное изображение
    const bestImage = imageUrls[0];

    return NextResponse.json({
      success: true,
      image: {
        url: bestImage,
        alt: searchQuery,
        source: "web_search"
      },
      alternatives: imageUrls.slice(1, 5)
    });

  } catch (error: any) {
    console.error("Image search error:", error);

    // Fallback на Unsplash
    return NextResponse.json({
      success: true,
      image: {
        url: `https://source.unsplash.com/1200x800/?delivery,courier`,
        alt: "Курьер доставка",
        source: "unsplash_fallback",
        credit: "Unsplash",
        license: "Free to use"
      },
      fallback: true,
      error: error.message
    });
  }
}

// Проверка, является ли URL изображением
function isImageUrl(url: string): boolean {
  const imageExtensions = [".jpg", ".jpeg", ".png", ".gif", ".webp", ".bmp"];
  const lowerUrl = url.toLowerCase();
  return imageExtensions.some(ext => lowerUrl.includes(ext));
}

// Извлечение URL изображений из текста
function extractImageUrls(text: string): string[] {
  const urlRegex = /https?:\/\/[^\s<>"']+\.(?:jpg|jpeg|png|gif|webp)/gi;
  const matches = text.match(urlRegex) || [];
  return [...new Set(matches)];
}

// GET для проверки
export async function GET() {
  return NextResponse.json({
    status: "ok",
    service: "Image Finder for Yandex Courier Articles",
    version: "1.0.0"
  });
}
