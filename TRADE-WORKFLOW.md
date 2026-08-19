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
Short trade enquiry         (the Contact page)
      ↓
YOU REVIEW IT               Trade Enquiries → the enquiry
      ↓                     approve to apply · more info · decline
Private invitation          (a unique link, emailed, expires)
      ↓
Full trade application      (nine sections — invitation only)
      ↓
YOU REVIEW IT AGAIN         Users → the applicant
      ↓                     approve · more info · on hold · decline
Portal opens                they can now sign in and see pricing
```

Two reviews, and they mean different things:

- **The first** is only "may this business apply?". It is not an account.
- **The second** is the account: due diligence, licensing, AWRS, the lot.

---

## Step 1 — Somebody clicks "Apply for a Trade Account"

Every one of those buttons — on the login page, in the popup, anywhere you put
one — goes to the **short enquiry**, which is the Contact page.

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
- They get a confirmation email; you get a notification with a button straight
  to the review screen.

## Step 6 — The due-diligence review

**Users → click the applicant.** Two panels:

- **Trade account** — the decision, and a note box.
- **Trade account application** — every answer, section by section, with the
  documents linked.

| Choice | What it does |
|---|---|
| **Approved — portal active** | Emails "your trade account is open" **and lets them sign in** |
| **Further information required** | Emails them your note |
| **On hold** | Emails "your application is on hold" |
| **Declined** | Emails a decline with your note |

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
| **Trade Enquiries** (sidebar) | Stage one — the pipeline, and the first review |
| **Users** | Stage two — the applications, and the account decision |
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
