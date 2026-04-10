# Symfony bundle for donation applications

## Design
- Use best practices of domain driven design
- Bounded contexts: BCDonations, BCPayments, BCIdentity. These live in dedicated root directories.

## Architecture
- Follow clean architecture / ports adapters
- Follow CQRS pattern
- Validate architecture using phparkitect

## Testing
- **BEFORE running or fixing any tests, you MUST read the Testing section in README.md for setup requirements**
- README.md contains critical environment variables and setup steps required for different test types
- Do not loosen the 100% unit test coverage requirement in any way.

## Implementing functionality based on behat feature specification
- Read the feature and implement all necessary layers
- Make sure unit tests keep passing 

**Last Updated**: March 16, 2026
