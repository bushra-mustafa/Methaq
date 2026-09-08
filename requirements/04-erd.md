# مخطط العلاقات

```mermaid
erDiagram
    users ||--o{ events : owns
    users ||--o{ orders : places
    templates ||--o{ events : initializes
    templates ||--o{ template_asset_links : includes
    template_assets ||--o{ template_asset_links : reused_by
    events ||--|| event_designs : has
    events ||--o{ orders : purchases
    events ||--o{ rsvps : receives
    orders o|--o{ payment_webhooks : reconciles
    event_designs ||--o{ design_renders : renders
```

event_designs.event_id الفريد يمنع أكثر من تصميم للمناسبة. إنشاء المناسبة والتصميم في transaction واحدة يضمن وجود التصميم؛ FK وحده لا يفرض وجود الابن. قد يصل webhook قبل مطابقة الطلب ولذلك order_id اختياري مؤقتاً. final_render_path يشير للنسخة المنشورة، وdesign_json يحتفظ بالمسودة الحالية.
