import { NextRequest, NextResponse } from "next/server";

// YandexGPT Configuration
const YANDEX_OAUTH_TOKEN = "y0__xCfvKymARjB3RMgr-LivRY4Bzde3_n4H-tsQ3UnXgEAEYNeAg";
const YANDEX_FOLDER_ID = "b1g72166lmpl4j31mthc";

// Cache IAM token
let cachedIamToken: string | null = null;
let tokenExpiry: number = 0;

// Get IAM token from OAuth
async function getIamToken(): Promise<string> {
  // Check if we have a valid cached token
  if (cachedIamToken && Date.now() < tokenExpiry - 60000) {
    return cachedIamToken;
  }

  const response = await fetch("https://iam.api.cloud.yandex.net/iam/v1/tokens", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ yandexPassportOauthToken: YANDEX_OAUTH_TOKEN })
  });

  if (!response.ok) {
    throw new Error(`Failed to get IAM token: ${response.status}`);
  }

  const data = await response.json();
  cachedIamToken = data.iamToken;
  tokenExpiry = new Date(data.expiresAt).getTime();

  return cachedIamToken;
}

// Generate text with YandexGPT
async function generateWithYandexGPT(prompt: string, maxTokens: number = 4000): Promise<string> {
  const iamToken = await getIamToken();

  const response = await fetch("https://llm.api.cloud.yandex.net/foundationModels/v1/completion", {
    method: "POST",
    headers: {
      "Authorization": `Bearer ${iamToken}`,
      "Content-Type": "application/json"
    },
    body: JSON.stringify({
      modelUri: `gpt://${YANDEX_FOLDER_ID}/yandexgpt/latest`,
      completionOptions: {
        stream: false,
        temperature: 0.8,
        maxTokens: maxTokens
      },
      messages: [
        {
          role: "system",
          text: "Ты — опытный SEO-копирайтер, специализирующийся на контенте о работе в сфере доставки. Пишешь на русском языке. Всегда отвечай валидным JSON."
        },
        {
          role: "user",
          text: prompt
        }
      ]
    })
  });

  if (!response.ok) {
    const error = await response.text();
    throw new Error(`YandexGPT error: ${response.status} - ${error}`);
  }

  const data = await response.json();
  return data.result?.alternatives?.[0]?.message?.text || "";
}

// Keywords for SEO
const KEYWORDS = [
  "работа курьером яндекс еда",
  "вакансии курьера яндекс",
  "курьер яндекс еда зарплата",
  "как стать курьером яндекс",
  "работа в яндекс еда",
  "курьер на велосипеде",
  "курьер пешком",
  "доставка еды яндекс",
  "подработка курьером",
  "заработок курьера яндекс"
];

// Article topics
const ARTICLE_TOPICS = [
  "Как начать работать курьером Яндекс Еда: пошаговая инструкция",
  "Зарплата курьера Яндекс Еда в 2025 году",
  "Советы опытных курьеров: как увеличить заработок",
  "График работы курьера Яндекс Еда",
  "Требования к курьерам Яндекс Еда",
  "Работа курьером на велосипеде: преимущества",
  "Как оформить ИП для работы курьером",
  "Частые ошибки начинающих курьеров",
  "Бонусы и акции для курьеров в 2025 году",
  "Безопасность курьера: правила",
  "Как работать курьером студенту",
  "Оборудование курьера: что нужно купить",
  "Как курьеру работать в плохую погоду",
  "Карьерный рост курьера в Яндекс Еда"
];

const DEFAULT_REFERRAL_LINK = "https://reg.eda.yandex.ru/?advertisement_campaign=forms_for_agents&user_invite_code=7dc31006022f4ab4bfa385dbfcc893b2&utm_content=blank";

export async function POST(request: NextRequest) {
  try {
    const body = await request.json().catch(() => ({}));

    const topic = ARTICLE_TOPICS[Math.floor(Math.random() * ARTICLE_TOPICS.length)];
    const selectedKeywords = KEYWORDS.sort(() => Math.random() - 0.5).slice(0, 4);
    const referralLink = body.referralLink || DEFAULT_REFERRAL_LINK;

    console.log(`[Article Generator] Generating: ${topic}`);

    const prompt = `Ты — профессиональный SEO-копирайтер для сайта о работе курьером в Яндекс Еда. Напиши полноценную статью на тему: "${topic}"

ВАЖНЫЕ ТРЕБОВАНИЯ:
1. Статья должна быть полезной, информативной и уникальной
2. Объём: минимум 1000 слов
3. Используй ключевые слова: ${selectedKeywords.join(", ")}
4. Структура статьи с подзаголовками H2 и H3
5. Включи списки где уместно
6. Тон: дружелюбный, мотивирующий

ССЫЛКА ДЛЯ РЕГИСТРАЦИИ (вставь 2-3 раза):
${referralLink}

Для каждого призыва к действию используй HTML:
<div style="text-align: center; margin: 20px 0;">
<a href="${referralLink}" style="display: inline-block; background: linear-gradient(135deg, #FFD500, #FFC300); color: #000; padding: 15px 30px; border-radius: 8px; text-decoration: none; font-weight: bold;">Стать курьером</a>
</div>

Ответь ТОЛЬКО валидным JSON (без markdown):
{
  "title": "SEO заголовок до 60 символов",
  "metaDescription": "Описание до 160 символов с ключевыми словами",
  "focusKeyword": "главное ключевое слово",
  "keywords": ["ключ1", "ключ2", "ключ3"],
  "excerpt": "Краткое описание 2-3 предложения",
  "content": "HTML контент с h2, h3, p, ul, li, strong"
}`;

    const responseText = await generateWithYandexGPT(prompt, 4000);

    // Parse JSON from response
    let article;
    try {
      const jsonMatch = responseText.match(/\{[\s\S]*\}/);
      if (jsonMatch) {
        article = JSON.parse(jsonMatch[0]);
      } else {
        throw new Error("No JSON found");
      }
    } catch {
      // If parsing fails, create article from text
      article = {
        title: topic,
        metaDescription: `Статья о работе курьером в Яндекс Еда. ${selectedKeywords.slice(0, 2).join(", ")}`,
        focusKeyword: selectedKeywords[0],
        keywords: selectedKeywords,
        excerpt: `В этой статье мы расскажем о ${topic.toLowerCase()}.`,
        content: responseText
      };
    }

    // Build result
    const result = {
      title: article.title,
      metaDescription: article.metaDescription,
      focusKeyword: article.focusKeyword,
      keywords: article.keywords || selectedKeywords,
      excerpt: article.excerpt,
      content: article.content,
      image: {
        url: `https://source.unsplash.com/1200x800/?delivery,courier`,
        alt: article.title || topic
      },
      metadata: {
        generatedAt: new Date().toISOString(),
        topic: topic,
        wordCount: article.content?.replace(/<[^>]*>/g, "").split(/\s+/).length || 0
      }
    };

    console.log(`[Article Generator] Success: ${result.title} (${result.metadata.wordCount} words)`);

    return NextResponse.json({
      success: true,
      article: result
    });

  } catch (error: any) {
    console.error("[Article Generator] Error:", error);
    return NextResponse.json({ error: error.message }, { status: 500 });
  }
}

// GET for status check
export async function GET() {
  try {
    // Test YandexGPT connection
    const iamToken = await getIamToken();

    return NextResponse.json({
      status: "ok",
      service: "Yandex Courier Article Generator (YandexGPT)",
      version: "2.0.0",
      yandexConnected: !!iamToken
    });
  } catch (error: any) {
    return NextResponse.json({
      status: "error",
      error: error.message
    }, { status: 500 });
  }
}
