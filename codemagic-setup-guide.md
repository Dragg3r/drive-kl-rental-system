# Codemagic iOS Build Setup for Drive KL Rental System

## Overview
I've created a complete Codemagic configuration (`codemagic.yaml`) that will automatically build your iOS app from your GitHub repository.

## Prerequisites

### 1. Apple Developer Account
- **Required**: Apple Developer Program membership ($99/year)
- **Account ID**: Your Apple Developer Team ID
- **Bundle ID**: `com.drivekl.rental` (already configured)

### 2. App Store Connect
- App must be created in App Store Connect
- App ID: `com.drivekl.rental`
- App name: "Drive KL Rental System by Akib"

## Step-by-Step Setup

### Step 1: Create Codemagic Account

1. **Sign up at**: https://codemagic.io/
2. **Sign in with GitHub** account
3. **Connect your repository**: `Dragg3r/drive-kl-rental-system`

### Step 2: Configure Apple Developer Integration

1. **In Codemagic Dashboard:**
   - Go to **Teams** → **Integrations**
   - Click **Apple Developer Portal**
   - Enter your Apple Developer credentials

2. **Add App Store Connect Integration:**
   - Go to **Integrations** → **App Store Connect**
   - Add your App Store Connect API key
   - Enter your Issuer ID and Key ID

### Step 3: Set Up iOS Certificates

1. **Automatic Certificate Management (Recommended):**
   - Codemagic will automatically generate certificates
   - Uses your Apple Developer account

2. **Manual Certificate Upload (Alternative):**
   - Upload your `.p12` certificate file
   - Upload provisioning profiles

### Step 4: Configure Environment Variables

In Codemagic, add these environment groups:

**Group: `ios_credentials`**
- `APP_STORE_CONNECT_APP_ID`: Your app ID from App Store Connect
- `APPLE_DEVELOPER_TEAM_ID`: Your Apple Developer Team ID
- `BUNDLE_ID`: `com.drivekl.rental`

### Step 5: Upload Configuration File

1. **Commit the `codemagic.yaml` file** to your GitHub repository
2. **Push to main branch**
3. **Codemagic will automatically detect** the configuration

### Step 6: Start Your First Build

1. **In Codemagic Dashboard:**
   - Select your repository
   - Choose the `ios-build` workflow
   - Click **Start new build**

2. **Build Process:**
   - Installs Node.js dependencies
   - Builds the web app (`npm run build`)
   - Syncs Capacitor with iOS
   - Installs iOS dependencies (CocoaPods)
   - Sets up certificates and provisioning
   - Builds the iOS app
   - Creates `.ipa` file

### Step 7: Download or Deploy

**Options after successful build:**
- **Download IPA**: Install on device via Xcode
- **TestFlight**: Automatically uploaded for beta testing
- **App Store**: Ready for App Store submission

## Configuration Details

### Build Settings
- **Instance**: Mac Mini M1 (fast builds)
- **Node.js**: Version 18.17.0
- **Xcode**: Latest version
- **Build time**: ~15-30 minutes

### Automatic Features
- **Certificate management**: Auto-generates certificates
- **Version increment**: Auto-increments build numbers
- **TestFlight upload**: Automatic after successful build
- **Email notifications**: Build success/failure alerts

### Build Artifacts
- **IPA file**: Ready for installation/distribution
- **Build logs**: Detailed build information
- **Debug symbols**: For crash reporting

## Troubleshooting

### Common Issues:

**1. Certificate Problems:**
- Ensure Apple Developer account is active
- Check Team ID is correct
- Verify bundle ID matches App Store Connect

**2. Build Failures:**
- Check build logs in Codemagic dashboard
- Verify all dependencies are installed
- Ensure Capacitor sync completed successfully

**3. Provisioning Issues:**
- Bundle ID must match exactly: `com.drivekl.rental`
- Apple Developer account must have app creation permissions

### Build Log Locations:
- **Codemagic Dashboard** → **Builds** → Select build → **Logs**
- Download detailed logs for debugging

## Testing Your iOS App

### Option 1: TestFlight (Recommended)
1. Build uploads automatically to TestFlight
2. Install TestFlight app on iPhone
3. Accept beta invitation
4. Install and test your app

### Option 2: Direct Installation
1. Download IPA file from Codemagic
2. Install via Xcode on connected iPhone
3. Trust developer certificate in iPhone settings

## Cost Estimation

### Codemagic Pricing:
- **Free tier**: 500 build minutes/month
- **Starter**: $28/month for 1,000 minutes
- **Professional**: $68/month for 2,500 minutes

### Build Time:
- **Typical build**: 15-20 minutes
- **First build**: 25-30 minutes (dependency downloads)

## Next Steps After Setup

1. **Push your code** to GitHub (if not already done)
2. **Complete Codemagic account setup** with Apple credentials
3. **Start first build** using the configuration
4. **Test the app** via TestFlight or direct installation
5. **Iterate and improve** based on testing feedback

## Production Deployment

For App Store release:
1. Update `codemagic.yaml`: Set `submit_to_app_store: true`
2. Ensure app metadata is complete in App Store Connect
3. Submit for Apple review
4. Release to App Store after approval

Your Drive KL Rental System is now ready for professional iOS app builds! 🚀