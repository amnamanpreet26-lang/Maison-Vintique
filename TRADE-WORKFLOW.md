# How a trade account gets opened

Everything from a stranger clicking a button to a customer signing in, in the
order it happens, with the screens you use at each step.

**Nothing on this site opens a trade account automatically.** A person decides,
twice.

---

## The short version

```
Apply for a Trade Account   (a button, anywhere on the site)
      ↓
Short trade enquiry         (a popup — the Contact page without JavaScript)
      ↓
YOU REVIEW IT               Trade Enquiries → the enquiry
      ↓                     approve to apply · more info · decline
Private invitation          (a unique link, emailed, expires)
      ↓
Full trade application      (nine sections — invitation only)
      ↓
YOU REVIEW IT AGAIN         Trade Enquiries → the same enquiry
      ↓                     approve · more info · on hold · decline
Portal opens                they can now sign in and see pricing
```

**One screen for all of it.** Trade Enquiries carries the enquiry, the
invitation, the full application, both reviews and a dated history of
everything that has happened. You never have to go looking somewhere else to
find out where a business has got to.

Two reviews, and they mean different things:

- **The first** is only "may this business apply?". It is not an account.
- **The second** is the account: due diligence, licensing, AWRS, the lot.

---

## Step 1 — Somebody clicks "Apply for a Trade Account"

Every one of those buttons — on the login page, in the trade pricing popup, a
menu item, an Elementor button, anywhere you put one — **opens the short
enquiry as a popup, over whatever they were reading**. They do not leave the
page.

You do not have to do anything to a button to get this. Any link pointing at
the enquiry page is upgraded automatically.

With JavaScript switched off, every one of those buttons is still a real link
to the enquiry page, and that page still works exactly as it did. Nothing is
lost, it is just a page load instead of a popup.

None of them goes to the full application, and none of them can. The
destination is worked out in one place in the code, and the application is
deliberately not on the list.

## Step 2 — They fill in the short enquiry

Nine questions, no account, no password:

business name · contact name · business email · telephone ·
website or social media · business type · town or city ·
about the business · what interests them

The button says **Request a Trade Account Application**.

**What you change, and where**

| Thing | Where |
|---|---|
| Heading, intro, contact details, button label | Edit the Contact page — all ACF fields |
| The questions themselves | `mve_enquiry_fields()` in `inc/trade-enquiries.php` |

**What happens the moment they press it**

- The enquiry is stored under **Trade Enquiries** in the sidebar.
- They get an acknowledgement email with "what happens next".
- You get a notification email with every answer and a button to the review
  screen.
- On screen they see a confirmation with the three steps of the process, so
  nobody sits waiting for an account that was never coming.

## Step 3 — You review the enquiry

**Trade Enquiries → click the business name.**

The left panel shows what they told you. The right panel is the decision:

| Choice | What it does |
|---|---|
| **Approve to apply** | Generates a private link and emails it to them |
| **Request further information** | Emails them your note and waits |
| **Decline — not suitable at present** | Emails a short, courteous decline |

Write your note in the box first — it goes into the second and third emails.
It is not sent with an approval.

> Approving here means **approved to apply**. It is not the trade account. The
> invitation email says so in as many words.

The list view shows every enquiry with its status and whether its invitation is
still valid, so you can see the whole pipeline at a glance.

## Step 4 — The invitation

The link is:

- **unique to that enquiry** — it is not a page anybody can find or guess;
- **time-limited** — 21 days by default (change it at the top of
  WooCommerce → Settings → Emails);
- **single use** — it stops working the moment their application is submitted;
- **replaced if you re-issue it** — approve the same enquiry again and the old
  link dies, so a forwarded link cannot be used later.

The application page is also `noindex`, kept out of site search and out of the
sitemap. Without a valid link, anybody who reaches it sees a short "by
invitation" page pointing them at the enquiry form.

**A copy of the link is on the review screen** in case they lose the email.

## Step 5 — They complete the full application

Nine sections: business information, primary contacts, authorised website and
order users, alcohol licensing, AWRS status, billing and delivery, payment,
supporting documents, and the declarations with a typed signature.

Everything they already told you at the enquiry stage is **filled in for them**
— business name, contact, email, telephone, website, business type. Nobody is
asked the same question twice.

Above the submit button, in bold: *completing this form does not guarantee
approval.*

**What happens on submit**

- A customer account is created, status **Pending review**. It cannot be signed
  into yet.
- Every answer is stored against that account; uploaded documents go into the
  media library as **private** files.
- The invitation is spent and the enquiry moves to "Full application submitted".
- **They see a thank-you panel** naming the address the confirmation went to.
- **They get a confirmation email. You get a notification** with a button
  straight to the review screen. If the branded email cannot go out for any
  reason, a plain-text copy goes instead — both sides are told either way.

**If the submit seems to do nothing**

There is one way a submit can vanish that is not the theme's doing: if the
application plus its attachments is larger than the server's `post_max_size`,
PHP throws the whole request away before any code runs. The applicant lands on
a blank screen — no message, no emails, nothing recorded.

That is now caught in two places:

- **Before it is sent.** Attachments are weighed as they are chosen, and if
  they come to more than the server will take, the form says so and refuses to
  submit until they are smaller.
- **After it arrives.** If a discarded submission gets through anyway, they are
  sent back to their own application — invitation intact — with a message
  naming the size they sent and the limit here.

Raising the limit is a hosting setting (`post_max_size`, and
`upload_max_filesize`), not a theme one. 8MB is a common default and is tight
for an application with several documents attached; 32MB is comfortable.

## Step 6 — The due-diligence review

**Trade Enquiries → click the business.** The same screen you did the first
review on — the whole process is in one place, and it updates as you go.

- **The enquiry** — what they first told you.
- **The full application** — every answer, section by section, with the
  documents linked.
- **Where this has got to** — the whole history, with dates.
- **Due-diligence review** (the box on the right) — the decision, and a note
  box. The box's title changes with the stage, so it never claims to be the
  initial review when it is offering the account decision.

| Choice | What it does |
|---|---|
| **Approve the trade account** | Emails "your trade account is open" **and lets them sign in** |
| **Request further information** | Emails them your note. The account stays closed |
| **Put on hold** | Emails "your application is on hold" |
| **Decline the application** | Emails a decline with your note |

Choose one, then **Update**. The email goes out as you save.

The applicant's own profile under **Users** still shows the application and
still takes the same decision — the two screens are the same data and stay in
step whichever one you use. You should not need to go there.

## Step 7 — The portal opens

Approval is what activates the account. Until then, signing in is refused with
a message that matches their status — "your application is with us", "we need a
little more", "on hold" — rather than a wrong-password error.

On their first sign-in they get the Welcome email, once.

**This only applies to accounts created by the application.** Anyone created
before this workflow existed, and every administrator and shop manager, signs
in exactly as before. Nobody gets locked out of the site by it.

---

## Where everything lives

### Screens you will use

| Screen | For |
|---|---|
| **Trade Enquiries** (sidebar) | **The whole process, start to finish** — the pipeline, both reviews, the full application and the history |
| **Users** | The account itself. The same application and the same decision also appear here; you should not need them |
| **WooCommerce → Settings → Emails** | Who gets notified, every email's wording, invitation expiry |
| **WooCommerce → Emails** | Look at any email; send yourself a test; check mail is working at all |
| **Appearance → Customize → Site Identity** | The crest used in the header, the footer and every email |

### Every email, and who gets it

| When | To them | To you |
|---|---|---|
| Short enquiry submitted | Thank you for your enquiry | New trade enquiry |
| You approve the enquiry | **You are invited to apply** (the private link) | — |
| You ask for more | A little more, please | — |
| You decline | About your enquiry | — |
| Full application submitted | Application received | New trade application |
| You approve the account | Your trade account is open | — |
| You ask for more / hold / decline | About your application · On hold · Declined | — |
| First sign-in after approval | Welcome | — |
| Order placed and paid | WooCommerce's Processing | WooCommerce's New order |
| Order placed, awaiting approval | Order request received | New order request |
| Marked Shipped | Your wine is on its way | — |
| Proforma / chasing payment | Invoice · Payment required · Payment reminder — sent by hand from the order screen's Actions box | — |
| Password or account details changed | Security notice | — |
| Waitlisted wine back in stock | Back in stock | — |

Every one of those is listed at **WooCommerce → Settings → Emails** with its own
on/off switch, subject line and heading.

### Setting it up, once

1. **Pages → Add New**, call it *Contact* (or *Trade Enquiries*), Page
   Attributes → Template → **Contact**. Publish. That is now the enquiry form,
   and every "Apply" button points at it automatically.
2. **Pages → Add New**, call it *Trade Account Application*, Page Attributes →
   Template → **Trade Account Application**. Publish. **Do not put it in a
   menu** — it is reached by invitation only.
3. **WooCommerce → Settings → Emails**, top of the page: the address you want
   notifications on, your address and telephone for the email footer, and how
   long invitations last.
4. **Appearance → Customize → Site Identity**: upload the crest if you want a
   different one from the theme's.
5. **WooCommerce → Emails**: send yourself a test and check the panel says mail
   is going out through a real mail service.

### If emails are not arriving

**WooCommerce → Emails** answers this. It reports how the site is sending mail,
whether the "from" address is on your own domain, the last message it handed
over and the last one refused.

Nine times in ten the answer is that WordPress is asking the web server to send
mail directly. Most hosts block that, and mail from a hosting IP without
authentication is filed as spam by Gmail and Outlook regardless. **Install WP
Mail SMTP or FluentSMTP and point it at a real mail service** — Brevo,
Postmark, SendGrid, Google Workspace or your own mailbox.

### Changing the process

| You want to | Do this |
|---|---|
| Add a question to the short enquiry | `mve_enquiry_fields()` in `inc/trade-enquiries.php` |
| Add a question to the full application | `mve_application_schema()` in `inc/trade-application.php`, or the `mve_application_schema` filter |
| Change how long invitations last | WooCommerce → Settings → Emails |
| Change any email's words | WooCommerce → Settings → Emails for the subject and heading; `inc/emails/class-mve-emails.php` for the body |
| Let people sign in before approval | `add_filter( 'mve_require_approval_to_sign_in', '__return_false' );` |
| Put WooCommerce's self-service registration back | `add_filter( 'mve_show_registration_form', '__return_true', 20 );` |
| Push enquiries or applications into a CRM | `do_action( 'mve_enquiry_status_changed', … )` and `do_action( 'mve_trade_application_received', … )` |

### The files behind it

| File | Stage |
|---|---|
| `inc/contact-form.php` | The short enquiry form and its handler |
| `inc/trade-enquiries.php` | Enquiry records, the first review, invitation tokens |
| `inc/trade-application.php` | The full application, the invitation gate, prefill |
| `inc/trade-accounts.php` | The due-diligence review and portal activation |
| `inc/emails/class-mve-emails.php` | Every email's wording |
| `inc/email-tools.php` | Notification address, mail log, diagnosis |
| `woocommerce/emails/` | The look of the emails |
