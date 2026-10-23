# Stripe fixtures

Stripe API objects for the billing tests, loaded with
`FakesStripeTrait::stripeFixture()` and overridden per test.

Built from Stripe's OpenAPI spec (`stripe/openapi`, `latest`, version
2026-09-30.endive): each holds every field the spec says the API always
returns, with realistic values where RYH or Cashier reads them. They aren't
recorded from a Stripe account. To refresh one, compare it with the spec's
`components.schemas.<object>.required` and Stripe's API reference.
