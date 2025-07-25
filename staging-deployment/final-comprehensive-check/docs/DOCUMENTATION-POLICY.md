# 📋 Documentation Policy - Gary AI WordPress Plugin

> **Official policy for documentation creation, organization, and maintenance**

## 📍 **Documentation Location Policy**

### 🎯 **Central Documentation Location**
**ALL documentation for the Gary AI WordPress plugin MUST be created and maintained in:**
```
/gary-ai/docs/
```

This policy ensures:
- **Centralized Access**: All documentation in one organized location
- **Professional Organization**: Enterprise-grade documentation structure
- **Easy Maintenance**: Single location for updates and version control
- **Clear Navigation**: Comprehensive index system for all documentation
- **Consistent Structure**: Standardized documentation format and organization

### 📚 **Documentation Types Covered**

All of the following documentation types must be stored in `/gary-ai/docs/`:

#### **Core Documentation**
- ✅ **README.md** - Main documentation index and overview
- ✅ **API Endpoints.md** - Complete API reference and examples
- ✅ **ARCHITECTURE.md** - System architecture and technical design

#### **Development Documentation**
- ✅ **CONTRIBUTING.md** - Development workflow and contribution guidelines
- ✅ **DOCKER-TESTING-GUIDE.md** - Complete Docker development environment
- ✅ **README-DOCKER-TESTING.md** - Quick Docker setup guide
- ✅ **database-verification-summary.md** - Database testing and validation

#### **Security & Compliance**
- ✅ **SECURITY.md** - Security policies and vulnerability reporting
- ✅ **PRIVACY-POLICY.md** - Privacy and data protection guidelines (if needed)
- ✅ **COMPLIANCE.md** - Regulatory compliance documentation (if needed)

#### **Build & Deployment**
- ✅ **README-ZIP-CREATION.md** - Production build and ZIP creation policy
- ✅ **DEPLOYMENT-GUIDE.md** - Production deployment procedures (if needed)
- ✅ **CHANGELOG.md** - Version history and release notes (if needed)

#### **User Documentation**
- ✅ **INSTALLATION-GUIDE.md** - Installation instructions (if needed)
- ✅ **CONFIGURATION-GUIDE.md** - Configuration and setup guide (if needed)
- ✅ **TROUBLESHOOTING.md** - Common issues and solutions (if needed)

#### **Testing Documentation**
- ✅ **TESTING-GUIDE.md** - Testing procedures and guidelines (if needed)
- ✅ **PERFORMANCE-TESTING.md** - Performance testing documentation (if needed)
- ✅ **ACCESSIBILITY-TESTING.md** - Accessibility compliance testing (if needed)

## 🔧 **Documentation Maintenance Guidelines**

### **1. File Creation Standards**
- **Location**: All new documentation files MUST be created in `/gary-ai/docs/`
- **Naming**: Use clear, descriptive filenames with hyphens (kebab-case)
- **Format**: Use Markdown (.md) format for all documentation
- **Headers**: Start with H1 title and brief description

### **2. Index Maintenance**
- **Update README.md**: Always update the main documentation index when adding new files
- **Categorize Properly**: Place new documentation in appropriate categories
- **Link Correctly**: Ensure all internal links use proper relative paths
- **Describe Purpose**: Add clear descriptions for each documentation file

### **3. Content Standards**
- **Professional Tone**: Maintain professional, clear, and concise writing
- **Complete Coverage**: Ensure comprehensive coverage of topics
- **Code Examples**: Include relevant code examples and usage samples
- **Visual Aids**: Use tables, lists, and formatting for clarity
- **Regular Updates**: Keep documentation current with code changes

### **4. Version Control**
- **Git Tracking**: All documentation changes must be committed to Git
- **Change Logs**: Document significant changes in commit messages
- **Review Process**: Documentation changes should follow the same review process as code
- **Consistency**: Maintain consistent formatting and style across all documents

## 📁 **Folder Structure**

The `/gary-ai/docs/` folder should maintain this organization:

```
gary-ai/docs/
├── README.md                           # Main documentation index
├── DOCUMENTATION-POLICY.md             # This policy document
├── API Endpoints.md                    # API reference
├── ARCHITECTURE.md                     # System architecture
├── CONTRIBUTING.md                     # Development guidelines
├── SECURITY.md                         # Security policies
├── DOCKER-TESTING-GUIDE.md            # Docker development
├── README-DOCKER-TESTING.md           # Quick Docker setup
├── README-ZIP-CREATION.md             # Build policies
├── database-verification-summary.md   # Database testing
└── [additional documentation files]    # Future documentation
```

## 🚫 **What NOT to Store in Root Directories**

**NEVER create documentation files in these locations:**
- ❌ Project root (`/`)
- ❌ Gary AI root (`/gary-ai/`)
- ❌ Other subdirectories (`/gary-ai/includes/`, `/gary-ai/assets/`, etc.)
- ❌ WordPress root or other plugin directories

**Exception**: Only technical configuration files (like `docker-compose.yml`, `package.json`) should remain in their appropriate technical locations.

## 🔄 **Migration Procedure**

When documentation files are found outside `/gary-ai/docs/`:

1. **Move the File**: Relocate to `/gary-ai/docs/` directory
2. **Update Index**: Add to the main README.md index
3. **Fix Links**: Update any internal or external links
4. **Test Navigation**: Verify all links work correctly
5. **Commit Changes**: Commit the reorganization to Git

## 👥 **Enforcement**

### **Developer Responsibilities**
- Always create new documentation in `/gary-ai/docs/`
- Update the documentation index when adding files
- Follow established naming and formatting conventions
- Review documentation changes as part of code review process

### **Code Review Requirements**
- Verify documentation changes follow this policy
- Ensure new documentation is properly indexed
- Check for broken links or formatting issues
- Validate that documentation matches code changes

### **Automated Checks** (Future Implementation)
- Git hooks to validate documentation location
- Automated link checking in CI/CD pipeline
- Documentation coverage analysis
- Style and formatting validation

## 📞 **Questions and Updates**

### **Policy Questions**
If you have questions about this documentation policy:
1. Check existing documentation in `/gary-ai/docs/`
2. Review this policy document for guidance
3. Ask in project discussions or issues
4. Follow established code review process

### **Policy Updates**
This policy document may be updated to:
- Clarify documentation standards
- Add new documentation categories
- Improve organization guidelines
- Reflect project evolution

All policy updates must:
- Be discussed with the development team
- Follow the same review process as code changes
- Maintain backward compatibility when possible
- Be clearly documented in commit messages

---

> **Remember**: Consistent documentation organization is crucial for project maintainability, user experience, and professional presentation. Always use `/gary-ai/docs/` as the central hub for all project documentation.

**Policy Established**: January 2025  
**Last Updated**: January 2025  
**Version**: 1.0.0 